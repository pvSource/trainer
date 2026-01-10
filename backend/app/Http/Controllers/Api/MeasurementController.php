<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Models\MeasurementUser;
use App\Models\Muscle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeasurementController extends Controller
{
    /**
     * Получить список всех типов замеров с замерми текущего пользователя
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Получаем все типы замеров с мышцами
        // Используем eager loading для оптимизации запросов
        $measurements = Measurement::with([
            'muscles' => function ($query) {
                $query->select('id', 'name', 'code');
            },
            'measurementUsers' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orderBy('measure_at', 'desc');
            }
        ])->get();

        // Преобразуем данные для ответа
        $result = $measurements->map(function ($measurement) {
            return [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'code' => $measurement->code,
                'unit' => $measurement->unit,
                'description' => $measurement->description,
                'muscles' => $measurement->muscles->map(function ($muscle) {
                    return [
                        'id' => $muscle->id,
                        'name' => $muscle->name,
                        'code' => $muscle->code,
                    ];
                }),
                'user_measurements' => $measurement->measurementUsers->map(function ($userMeasurement) {
                    return [
                        'id' => $userMeasurement->id,
                        'value' => (float) $userMeasurement->value,
                        'measure_at' => $userMeasurement->measure_at->format('Y-m-d'),
                        'created_at' => $userMeasurement->created_at->toDateTimeString(),
                    ];
                }),
            ];
        });

        return response()->json([
            'measurements' => $result,
        ]);
    }

    /**
     * Получить конкретный тип замера с замерми текущего пользователя
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $measurement = Measurement::with([
            'muscles' => function ($query) {
                $query->select('id', 'name', 'code');
            },
            'measurementUsers' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orderBy('measure_at', 'desc');
            }
        ])->findOrFail($id);

        return response()->json([
            'id' => $measurement->id,
            'name' => $measurement->name,
            'code' => $measurement->code,
            'unit' => $measurement->unit,
            'description' => $measurement->description,
            'muscles' => $measurement->muscles->map(function ($muscle) {
                return [
                    'id' => $muscle->id,
                    'name' => $muscle->name,
                    'code' => $muscle->code,
                ];
            }),
            'user_measurements' => $measurement->measurementUsers->map(function ($userMeasurement) {
                return [
                    'id' => $userMeasurement->id,
                    'value' => (float) $userMeasurement->value,
                    'measure_at' => $userMeasurement->measure_at->format('Y-m-d'),
                    'created_at' => $userMeasurement->created_at->toDateTimeString(),
                ];
            }),
        ]);
    }

    /**
     * Получить данные для таблицы замеров
     * Формат: строки = типы замеров, столбцы = даты, ячейки = значения
     */
    public function table(Request $request)
    {
        $user = $request->user();

        // Получаем все типы замеров
        $measurements = Measurement::orderBy('name')->get();

        // Получаем все уникальные даты замеров для текущего пользователя
        $dateList = MeasurementUser::where('user_id', $user->id)
            ->select(DB::raw('DISTINCT DATE(measure_at) as date'))
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                $date = $item->date;
                if ($date instanceof \Carbon\Carbon) {
                    return $date->format('Y-m-d');
                }
                if (is_string($date)) {
                    return date('Y-m-d', strtotime($date));
                }
                return $date;
            })
            ->unique()
            ->values()
            ->toArray();

        // Получаем все значения замеров пользователя, сгруппированные по measurement_id и дате
        $values = MeasurementUser::where('user_id', $user->id)
            ->select('measurement_id', DB::raw('DATE(measure_at) as date'), 'value', 'id')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                $date = $item->date;
                if ($date instanceof \Carbon\Carbon) {
                    $item->date = $date->format('Y-m-d');
                } elseif (is_string($date)) {
                    $item->date = date('Y-m-d', strtotime($date));
                }
                return $item;
            })
            ->groupBy(['measurement_id', 'date']);

        // Формируем структуру для таблицы
        $tableData = $measurements->map(function ($measurement) use ($dateList, $values) {
            $row = [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'code' => $measurement->code,
                'unit' => $measurement->unit,
                'values' => []
            ];

            // Для каждой даты находим значение для этого типа замера
            foreach ($dateList as $dateStr) {
                $value = null;
                $valueId = null;

                if (isset($values[$measurement->id][$dateStr])) {
                    // Если есть несколько значений на одну дату, берем последнее
                    $measurementValue = $values[$measurement->id][$dateStr]->last();
                    $value = (float) $measurementValue->value;
                    $valueId = $measurementValue->id;
                }

                $row['values'][$dateStr] = [
                    'value' => $value,
                    'id' => $valueId,
                ];
            }

            return $row;
        });

        return response()->json([
            'measurements' => $tableData,
            'dates' => $dateList,
        ]);
    }

    /**
     * Добавить новое значение замера
     */
    public function storeValue(Request $request, int $id)
    {
        $user = $request->user();

        // Проверяем, что тип замера существует
        $measurement = Measurement::findOrFail($id);

        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
            'measure_at' => 'required|date',
        ]);

        $measurementUser = MeasurementUser::create([
            'measurement_id' => $id,
            'user_id' => $user->id,
            'value' => $validated['value'],
            'measure_at' => $validated['measure_at'],
        ]);

        return response()->json([
            'message' => 'Значение замера успешно добавлено',
            'data' => [
                'id' => $measurementUser->id,
                'value' => (float) $measurementUser->value,
                'measure_at' => $measurementUser->measure_at->format('Y-m-d'),
            ],
        ], 201);
    }

    /**
     * Обновить значение замера
     */
    public function updateValue(Request $request, int $id, int $valueId)
    {
        $user = $request->user();

        // Проверяем, что тип замера существует
        Measurement::findOrFail($id);

        // Проверяем, что значение принадлежит пользователю и типу замера
        $measurementUser = MeasurementUser::where('id', $valueId)
            ->where('measurement_id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'value' => 'required|numeric|min:0',
            'measure_at' => 'required|date',
        ]);

        $measurementUser->update([
            'value' => $validated['value'],
            'measure_at' => $validated['measure_at'],
        ]);

        return response()->json([
            'message' => 'Значение замера успешно обновлено',
            'data' => [
                'id' => $measurementUser->id,
                'value' => (float) $measurementUser->value,
                'measure_at' => $measurementUser->measure_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Удалить значение замера
     */
    public function deleteValue(Request $request, int $id, int $valueId)
    {
        $user = $request->user();

        // Проверяем, что тип замера существует
        Measurement::findOrFail($id);

        // Проверяем, что значение принадлежит пользователю и типу замера
        $measurementUser = MeasurementUser::where('id', $valueId)
            ->where('measurement_id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $measurementUser->delete();

        return response()->json([
            'message' => 'Значение замера успешно удалено',
        ]);
    }
}
