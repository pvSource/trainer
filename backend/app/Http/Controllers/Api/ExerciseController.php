<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\Measurement;
use App\Models\MeasurementUser;
use App\Models\Muscle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Exercise::with(['muscles']);

        // Поиск по названию
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

//        if ($request->filled('muscle_id')) {
//            $muscleLevel = Muscle::findOrFail($request->filled('muscle_id'));
//        } elseif ($request->user()?->muscles_level) {
//            $muscleLevel = $request->user->muscles_level;
//        } else {
//            $muscleLevel = 3;
//        }


        // Фильтр по мышце + всем потомкам
        if ($request->filled('muscle_id')) {

            $muscleId = (int) $request->muscle_id;

            $query->whereHas('muscles', function ($q) use ($muscleId) {

                $q->whereIn('muscles.id', function ($sub) use ($muscleId) {

                    $sub->select('id')
                        ->from(DB::raw("
                    (
                        WITH RECURSIVE tree AS (
                            SELECT id, parent_id
                            FROM muscles
                            WHERE id = {$muscleId} AND is_primary = true

                            UNION ALL

                            SELECT m.id, m.parent_id
                            FROM muscles m
                            JOIN tree t ON m.parent_id = t.id
                        )
                        SELECT id FROM tree
                    ) AS subtree
                "));
                });
            });
        }

        $exercises = $query->paginate($request->get('per_page', 15));

        return response()->json($exercises);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'muscles' => 'nullable|array',
            'muscles.*.id' => 'required|exists:muscles,id',
            'muscles.*.is_primary' => 'boolean',
        ]);

        $exercise = Exercise::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Привязка мышц
        if (isset($validated['muscles'])) {
            foreach ($validated['muscles'] as $muscleData) {
                $exercise->muscles()->attach($muscleData['id'], [
                    'is_primary' => $muscleData['is_primary'] ?? false,
                ]);
            }
        }

        $exercise->load('muscles');

        return response()->json($exercise, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exercise = Exercise::with(['muscles', 'trainings'])->findOrFail($id);

        return response()->json($exercise);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $exercise = Exercise::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'muscles' => 'nullable|array',
            'muscles.*.id' => 'required|exists:muscles,id',
            'muscles.*.is_primary' => 'boolean',
        ]);

        $exercise->update([
            'name' => $validated['name'] ?? $exercise->name,
            'description' => $validated['description'] ?? $exercise->description,
        ]);

        // Обновление мышц
        if (isset($validated['muscles'])) {
            $exercise->muscles()->detach();
            foreach ($validated['muscles'] as $muscleData) {
                $exercise->muscles()->attach($muscleData['id'], [
                    'is_primary' => $muscleData['is_primary'] ?? false,
                ]);
            }
        }

        $exercise->load('muscles');

        return response()->json($exercise);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $exercise = Exercise::findOrFail($id);
        $exercise->delete();

        return response()->json(['message' => 'Exercise deleted successfully'], 200);
    }

    /**
     * Получить статистику по упражнению для текущего пользователя
     * Возвращает данные для графика: вес по датам, сгруппированные по количеству повторений
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request, int $id)
    {
        $user = $request->user();
        
        // Проверяем, что упражнение существует
        $exercise = Exercise::findOrFail($id);

        // Валидация параметров фильтрации по дате
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Получаем все тренировки пользователя с этим упражнением
        $query = DB::table('training_exercises')
            ->join('trainings', 'training_exercises.training_id', '=', 'trainings.id')
            ->where('trainings.user_id', $user->id)
            ->where('training_exercises.exercise_id', $id)
            ->whereNull('training_exercises.deleted_at')
            ->whereNull('trainings.deleted_at');

        // Применяем фильтры по дате, если они указаны
        if ($request->filled('date_from')) {
            $query->whereDate('trainings.start_at', '>=', $validated['date_from']);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trainings.start_at', '<=', $validated['date_to']);
        }

        $trainingExercises = $query
            ->select(
                'training_exercises.weight',
                'training_exercises.repetitions_count',
                'trainings.start_at as training_date',
                'trainings.id as training_id'
            )
            ->orderBy('trainings.start_at', 'asc')
            ->get();

        // Группируем данные по количеству повторений
        $series = [];
        
        foreach ($trainingExercises as $te) {
            $repetitions = $te->repetitions_count;
            $date = date('Y-m-d', strtotime($te->training_date));
            $weight = (float) $te->weight;
            $trainingId = (int) $te->training_id;

            // Если серии для этого количества повторений еще нет - создаем
            if (!isset($series[$repetitions])) {
                $series[$repetitions] = [];
            }

            // Добавляем точку данных (если для этой даты уже есть данные, берем максимальный вес)
            // Сохраняем training_id для точки с максимальным весом
            if (!isset($series[$repetitions][$date]) || $series[$repetitions][$date]['weight'] < $weight) {
                $series[$repetitions][$date] = [
                    'weight' => $weight,
                    'training_id' => $trainingId
                ];
            }
        }

        // Преобразуем в формат для графика
        $chartData = [];
        foreach ($series as $repetitions => $dataByDate) {
            $points = [];
            foreach ($dataByDate as $date => $data) {
                $points[] = [
                    'date' => $date,
                    'weight' => $data['weight'],
                    'training_id' => $data['training_id']
                ];
            }
            
            // Сортируем по дате
            usort($points, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            $chartData[] = [
                'repetitions' => $repetitions,
                'data' => $points
            ];
        }

        // Сортируем серии по количеству повторений
        usort($chartData, function($a, $b) {
            return $a['repetitions'] <=> $b['repetitions'];
        });

        return response()->json([
            'exercise_id' => $id,
            'exercise_name' => $exercise->name,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'series' => $chartData
        ]);
    }

    /**
     * Получить статистику по замерам тела для упражнения
     * Находит родительские мышцы уровня 2 для всех мышц упражнения
     * и возвращает данные по замерам, связанным с этими мышцами
     */
    public function measurementsStatistics(Request $request, int $id)
    {
        $user = $request->user();

        // Проверяем, что упражнение существует и загружаем мышцы
        $exercise = Exercise::with('muscles')->findOrFail($id);

        // Валидация параметров фильтрации по дате
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        // Получаем все мышцы упражнения
        $exerciseMuscles = $exercise->muscles;

        if ($exerciseMuscles->isEmpty()) {
            return response()->json([
                'exercise_id' => $id,
                'exercise_name' => $exercise->name,
                'measurements' => [],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ]);
        }

        // Находим родительские мышцы уровня 2 для всех мышц упражнения
        $parentMusclesLevel2 = collect();
        
        foreach ($exerciseMuscles as $muscle) {
            $parentLevel2 = $this->getMuscleParentByLevel($muscle, 2);
            if ($parentLevel2) {
                $parentMusclesLevel2->push($parentLevel2);
            }
        }

        // Убираем дубликаты по ID
        $parentMusclesLevel2 = $parentMusclesLevel2->unique('id');

        if ($parentMusclesLevel2->isEmpty()) {
            return response()->json([
                'exercise_id' => $id,
                'exercise_name' => $exercise->name,
                'measurements' => [],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ]);
        }

        // Находим все дочерние мышцы для каждой родительской мышцы уровня 2
        $allRelatedMuscleIds = collect();
        foreach ($parentMusclesLevel2 as $parentMuscle) {
            $children = $this->getAllDescendantsIds($parentMuscle->id);
            $allRelatedMuscleIds->push($parentMuscle->id);
            $allRelatedMuscleIds = $allRelatedMuscleIds->merge($children);
        }
        $allRelatedMuscleIds = $allRelatedMuscleIds->unique();

        // Находим все замеры, связанные с этими мышцами через measurement_muscles
        $measurements = Measurement::whereHas('muscles', function ($query) use ($allRelatedMuscleIds) {
            $query->whereIn('muscles.id', $allRelatedMuscleIds);
        })->get();

        if ($measurements->isEmpty()) {
            return response()->json([
                'exercise_id' => $id,
                'exercise_name' => $exercise->name,
                'measurements' => [],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ]);
        }

        // Для каждого замера получаем данные пользователя
        $chartData = [];
        
        foreach ($measurements as $measurement) {
            $query = MeasurementUser::where('user_id', $user->id)
                ->where('measurement_id', $measurement->id)
                ->whereNull('deleted_at');

            // Применяем фильтры по дате
            if ($request->filled('date_from')) {
                $query->whereDate('measure_at', '>=', $validated['date_from']);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('measure_at', '<=', $validated['date_to']);
            }

            $measurementUsers = $query->orderBy('measure_at', 'asc')->get();

            if ($measurementUsers->isEmpty()) {
                continue;
            }

            // Группируем по дате (если несколько значений на одну дату, берем последнее)
            $dataByDate = [];
            foreach ($measurementUsers as $mu) {
                $date = $mu->measure_at->format('Y-m-d');
                // Если для этой даты уже есть значение, берем последнее (более свежее)
                if (!isset($dataByDate[$date]) || $dataByDate[$date]['created_at'] < $mu->created_at) {
                    $dataByDate[$date] = [
                        'value' => (float) $mu->value,
                        'measure_at' => $date,
                        'created_at' => $mu->created_at
                    ];
                }
            }

            // Преобразуем в массив точек
            $points = [];
            foreach ($dataByDate as $date => $data) {
                $points[] = [
                    'date' => $data['measure_at'],
                    'value' => $data['value']
                ];
            }

            // Сортируем по дате
            usort($points, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });

            if (!empty($points)) {
                $chartData[] = [
                    'measurement_id' => $measurement->id,
                    'measurement_name' => $measurement->name,
                    'measurement_code' => $measurement->code,
                    'unit' => $measurement->unit,
                    'data' => $points
                ];
            }
        }

        // Сортируем по названию замера
        usort($chartData, function($a, $b) {
            return strcmp($a['measurement_name'], $b['measurement_name']);
        });

        return response()->json([
            'exercise_id' => $id,
            'exercise_name' => $exercise->name,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'measurements' => $chartData
        ]);
    }

    /**
     * Получить родительскую мышцу указанного уровня для данной мышцы
     */
    private function getMuscleParentByLevel(Muscle $muscle, int $targetLevel): ?Muscle
    {
        // Если текущая мышца уже на нужном уровне
        if ($muscle->level === $targetLevel) {
            return $muscle;
        }

        // Если уровень текущей мышцы меньше целевого, родителя не существует
        if ($muscle->level < $targetLevel) {
            return null;
        }

        // Поднимаемся вверх по иерархии
        $currentMuscle = $muscle;
        while ($currentMuscle && $currentMuscle->level > $targetLevel) {
            if ($currentMuscle->parent_id) {
                $currentMuscle = Muscle::find($currentMuscle->parent_id);
                if (!$currentMuscle) {
                    break;
                }
            } else {
                break;
            }
        }

        // Если нашли мышцу нужного уровня
        if ($currentMuscle && $currentMuscle->level === $targetLevel) {
            return $currentMuscle;
        }

        return null;
    }

    /**
     * Получить все ID дочерних мышц для заданной мышцы (рекурсивно)
     */
    private function getAllDescendantsIds(int $muscleId): array
    {
        $ids = [];
        $children = Muscle::where('parent_id', $muscleId)->get();
        
        foreach ($children as $child) {
            $ids[] = $child->id;
            $childIds = $this->getAllDescendantsIds($child->id);
            $ids = array_merge($ids, $childIds);
        }
        
        return $ids;
    }
}
