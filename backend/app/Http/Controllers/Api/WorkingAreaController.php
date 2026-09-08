<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkingArea;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkingAreaController extends Controller
{
    public function index(): JsonResponse
    {
        $areas = WorkingArea::all();

        return response()->json($areas);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:city,other',
            'points' => 'required|string',
            'geometry' => 'required|string',
        ]);

        $area = WorkingArea::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'points' => $validated['points'],
            'geometry' => $validated['geometry'],
        ]);

        return response()->json($area, 201);
    }

    public function show(WorkingArea $workingArea): JsonResponse
    {
        return response()->json($workingArea);
    }

    public function update(Request $request, WorkingArea $workingArea): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:city,other',
            'points' => 'sometimes|string',
            'geometry' => 'sometimes|string',
        ]);

        $workingArea->update($validated);

        return response()->json($workingArea);
    }

    public function destroy(WorkingArea $workingArea): JsonResponse
    {
        $workingArea->delete();

        return response()->json(null, 204);
    }
}
