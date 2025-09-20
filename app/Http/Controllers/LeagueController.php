<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = League::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or region
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $leagues = $query->withCount('clubs')
                        ->orderBy('name')
                        ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $leagues,
            'message' => 'Leagues retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $league = League::create($request->all());

        return response()->json([
            'data' => $league,
            'message' => 'League created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(League $league): JsonResponse
    {
        $league->load(['clubs' => function ($query) {
            $query->withCount('members');
        }]);

        return response()->json([
            'data' => $league,
            'message' => 'League retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, League $league): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $league->update($request->all());

        return response()->json([
            'data' => $league,
            'message' => 'League updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(League $league): JsonResponse
    {
        // Check if league has clubs
        if ($league->clubs()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete league with associated clubs',
                'errors' => ['league' => ['This league has clubs associated with it']],
            ], 422);
        }

        $league->delete();

        return response()->json([
            'message' => 'League deleted successfully',
        ]);
    }
}