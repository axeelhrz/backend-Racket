<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\League;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tournament::with(['league', 'sport']);

            // Filter by league if provided
            if ($request->has('league_id')) {
                $query->where('league_id', $request->league_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by sport if provided
            if ($request->has('sport_id')) {
                $query->where('sport_id', $request->sport_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%");
                });
            }

            $tournaments = $query->orderBy('start_date', 'desc')->get();

            return response()->json($tournaments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tournaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'league_id' => 'required|exists:leagues,id',
                'sport_id' => 'required|exists:sports,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'registration_deadline' => 'required|date|before:start_date',
                'max_participants' => 'required|integer|min:2',
                'entry_fee' => 'nullable|numeric|min:0',
                'prize_pool' => 'nullable|numeric|min:0',
                'tournament_format' => 'required|in:single_elimination,double_elimination,round_robin,swiss_system',
                'location' => 'nullable|string|max:255',
                'rules' => 'nullable|string',
                'status' => 'required|in:upcoming,active,completed,cancelled'
            ]);

            // Set default values
            $validated['current_participants'] = 0;
            $validated['matches_played'] = 0;
            $validated['matches_total'] = 0;
            $validated['entry_fee'] = $validated['entry_fee'] ?? 0;
            $validated['prize_pool'] = $validated['prize_pool'] ?? 0;

            $tournament = Tournament::create($validated);
            $tournament->load(['league', 'sport']);

            return response()->json($tournament, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating tournament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tournament $tournament): JsonResponse
    {
        try {
            $tournament->load(['league', 'sport', 'participants']);
            return response()->json($tournament);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tournament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'league_id' => 'required|exists:leagues,id',
                'sport_id' => 'required|exists:sports,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'registration_deadline' => 'required|date|before:start_date',
                'max_participants' => 'required|integer|min:2',
                'current_participants' => 'nullable|integer|min:0',
                'entry_fee' => 'nullable|numeric|min:0',
                'prize_pool' => 'nullable|numeric|min:0',
                'tournament_format' => 'required|in:single_elimination,double_elimination,round_robin,swiss_system',
                'location' => 'nullable|string|max:255',
                'rules' => 'nullable|string',
                'status' => 'required|in:upcoming,active,completed,cancelled',
                'matches_played' => 'nullable|integer|min:0',
                'matches_total' => 'nullable|integer|min:0'
            ]);

            // Set default values for optional fields
            $validated['entry_fee'] = $validated['entry_fee'] ?? 0;
            $validated['prize_pool'] = $validated['prize_pool'] ?? 0;
            $validated['current_participants'] = $validated['current_participants'] ?? $tournament->current_participants;
            $validated['matches_played'] = $validated['matches_played'] ?? $tournament->matches_played;
            $validated['matches_total'] = $validated['matches_total'] ?? $tournament->matches_total;

            $tournament->update($validated);
            $tournament->load(['league', 'sport']);

            return response()->json($tournament);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating tournament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tournament $tournament): JsonResponse
    {
        try {
            $tournament->delete();

            return response()->json([
                'message' => 'Tournament deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting tournament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournaments for a specific league
     */
    public function getByLeague(League $league): JsonResponse
    {
        try {
            $tournaments = Tournament::with(['sport'])
                ->where('league_id', $league->id)
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json($tournaments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching league tournaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournament statistics for a league
     */
    public function getLeagueStats(League $league): JsonResponse
    {
        try {
            $tournaments = Tournament::where('league_id', $league->id);

            $stats = [
                'total' => $tournaments->count(),
                'upcoming' => $tournaments->clone()->where('status', 'upcoming')->count(),
                'active' => $tournaments->clone()->where('status', 'active')->count(),
                'completed' => $tournaments->clone()->where('status', 'completed')->count(),
                'cancelled' => $tournaments->clone()->where('status', 'cancelled')->count(),
                'total_participants' => $tournaments->clone()->sum('current_participants'),
                'total_prize_pool' => $tournaments->clone()->sum('prize_pool'),
                'average_participants' => $tournaments->clone()->avg('current_participants') ?? 0
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching tournament statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}