<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Muscle;
use Illuminate\Http\Request;

class MuscleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Muscle::query();

        if ($request->has('level.gte')) {
            $query->where('level', '>=', $request->get('level.gte'));
        }
        if ($request->has('level.lte')) {
            $query->where('level', '<=', $request->get('level.lte'));
        }
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }

        $musclesArray = $query->get()->toArray();

        $grouped = [];
        foreach ($musclesArray as $element) {
            $parent = $element['parent_id'] ?? 0;
            $grouped[$parent][] = $element;
        }

        $build = function($parentId) use (&$build, $grouped) {
            $branch = [];
            if (!isset($grouped[$parentId])) return $branch;

            foreach ($grouped[$parentId] as $element) {
                $element['children'] = $build($element['id']);
                $branch[] = $element;
            }

            return $branch;
        };

        $musclesTree = $build(0); // корни с parent_id = 0 или null

        return response()->json($musclesTree);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $muscle = Muscle::findOrFail($id);
        return response()->json($muscle);
    }
}
