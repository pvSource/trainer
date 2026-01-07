<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

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

        // Фильтр по мышце
        if ($request->has('muscle_id')) {
            $query->whereHas('muscles', function ($q) use ($request) {
                $q->where('muscles.id', $request->muscle_id);
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
}
