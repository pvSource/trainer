<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
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
}
