<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sport::query();

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sports = $query->withCount('parameters')
                       ->orderBy('name')
                       ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $sports,
            'message' => 'Sports retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:sports,code',
        ]);

        $sport = Sport::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
        ]);

        return response()->json([
            'data' => $sport,
            'message' => 'Sport created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sport $sport): JsonResponse
    {
        $sport->load(['parameters' => function ($query) {
            $query->orderBy('param_key');
        }]);

        return response()->json([
            'data' => $sport,
            'message' => 'Sport retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sport $sport): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:sports,code,' . $sport->id,
        ]);

        $sport->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
        ]);

        return response()->json([
            'data' => $sport,
            'message' => 'Sport updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sport $sport): JsonResponse
    {
        // Check if sport has parameters
        if ($sport->parameters()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete sport with associated parameters',
                'errors' => ['sport' => ['This sport has parameters associated with it']],
            ], 422);
        }

        $sport->delete();

        return response()->json([
            'message' => 'Sport deleted successfully',
        ]);
    }
}