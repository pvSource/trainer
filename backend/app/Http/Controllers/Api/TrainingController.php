<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Training::with(['exercises'])->where('user_id', $request->user()->id);

        $trainings = $query
            ->orderBy('start_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($trainings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'finish_at' => 'nullable|date|after_or_equal:start_at',
            'exercises' => 'nullable|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.approaches_count' => 'nullable|integer',
            'exercises.*.repetitions_count' => 'nullable|integer',
            'exercises.*.weight' => 'nullable|numeric',
        ]);

        $training = Training::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'] ?? null,
            'description' => $validated['description'] ?? null,
            'start_at' => $validated['start_at'] ?? now(),
            'finish_at' => $validated['finish_at'] ?? null,
        ]);

        // Привязка упражнений
        if ($validated['exercises']) {
            foreach ($validated['exercises'] as $exerciseData) {
                $training->exercises()->attach($exerciseData['exercise_id'], [
                    'approaches_count' => $exerciseData['approaches_count'] ?? null,
                    'repetitions_count' => $exerciseData['repetitions_count'] ?? null,
                    'weight' => $exerciseData['weight'] ?? null,
                ]);
            }
        }

        $training->load('exercises');

        return response()->json($training, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $training = Training::with(['exercises', 'user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($training);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $training = Training::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'finish_at' => 'nullable|date|after_or_equal:start_at',
            'exercises' => 'sometimes|array',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.approaches_count' => 'required|integer|min:1',
            'exercises.*.repetitions_count' => 'required|integer|min:1',
            'exercises.*.weight' => 'nullable|numeric',
        ]);

        $training->update([
            'name' => $validated['name'] ?? $training->name,
            'description' => $validated['description'] ?? $training->description,
            'start_at' => $validated['start_at'] ?? $training->start_at,
            'finish_at' => $validated['finish_at'] ?? $training->finish_at,
        ]);

        // Обновление упражнений
        if (isset($validated['exercises'])) {
            $training->exercises()->detach();
            foreach ($validated['exercises'] as $exerciseData) {
                $training->exercises()->attach($exerciseData['exercise_id'], [
                    'approaches_count' => $exerciseData['approaches_count'],
                    'repetitions_count' => $exerciseData['repetitions_count'],
                    'weight' => $exerciseData['weight'] ?? null,
                ]);
            }
        }

        $training->load('exercises');

        return response()->json($training);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $training = Training::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $training->delete();

        return response()->json(['message' => 'Training deleted successfully'], 200);
    }
}
