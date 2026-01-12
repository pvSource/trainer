<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MuscleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MuscleController extends Controller
{

    public function __construct(
        private readonly MuscleService $muscleService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'level.gte' => ['nullable', 'integer', 'min:1', 'max:4'],
            'level.lte' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $result = $this->muscleService->getTree($validated['level.gte'] ?? 1, $validated['level.lte'] ?? 4);

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        return response()->json($this->muscleService->find($id));
    }
}
