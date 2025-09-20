<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Club;
use App\Models\League;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $query = Tournament::with(['club', 'league', 'sport']);

            // Filter based on user role
            if ($user->role === 'club_admin') {
                $query->whereHas('club', function ($q) use ($user) {
                    $q->where('admin_id', $user->id);
                });
            } elseif ($user->role === 'league_admin') {
                $query->whereHas('league', function ($q) use ($user) {
                    $q->where('admin_id', $user->id);
                });
            }

            $tournaments = $query->get();

            return response()->json($tournaments);
        } catch (\Exception $e) {
            Log::error('Error fetching tournaments: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching tournaments'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Tournament creation request:', $request->all());

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Simplified validation rules for debugging
            $rules = [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'registration_deadline' => 'required|date',
                'max_participants' => 'required|integer|min:2',
                'tournament_type' => 'required|in:individual,team',
                'status' => 'nullable|in:draft,published,in_progress,completed,cancelled',
                'club_id' => 'nullable|exists:clubs,id',
                'league_id' => 'nullable|exists:leagues,id',
                'sport_id' => 'nullable|exists:sports,id',
                'entry_fee' => 'nullable|numeric|min:0',
                
                // Basic fields
                'code' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'club_name' => 'nullable|string|max:255',
                'club_address' => 'nullable|string|max:500',
                'image' => 'nullable|string',
                
                // Individual tournament fields
                'modality' => 'nullable|in:singles,doubles',
                'elimination_type' => 'nullable|in:simple_elimination,direct_elimination,round_robin,mixed',
                'min_ranking' => 'nullable|integer|min:0',
                'max_ranking' => 'nullable|integer|min:0',
                'reminder_days' => 'nullable|in:7,15',
                
                // Team tournament fields
                'team_size' => 'nullable|integer|min:1',
                'min_age' => 'nullable|integer|min:0',
                'max_age' => 'nullable|integer|min:0',
                'gender_restriction' => 'nullable|in:male,female,mixed',
                'skill_level' => 'nullable|in:beginner,intermediate,advanced,professional',
                
                // Prize fields
                'first_prize' => 'nullable|string|max:500',
                'second_prize' => 'nullable|string|max:500',
                'third_prize' => 'nullable|string|max:500',
                'fourth_prize' => 'nullable|string|max:500',
                'fifth_prize' => 'nullable|string|max:500',
                
                // Contact fields
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
                'ball_info' => 'nullable|string|max:1000',
            ];

            $validatedData = $request->validate($rules);

            // Set default status if not provided
            if (!isset($validatedData['status'])) {
                $validatedData['status'] = 'draft';
            }

            // Set default entry_fee if not provided
            if (!isset($validatedData['entry_fee'])) {
                $validatedData['entry_fee'] = 0;
            }

            // Handle club_id based on user role
            if ($user->role === 'club_admin') {
                $club = Club::where('admin_id', $user->id)->first();
                if ($club) {
                    $validatedData['club_id'] = $club->id;
                }
            }

            // Handle league_id based on user role
            if ($user->role === 'league_admin') {
                $league = League::where('admin_id', $user->id)->first();
                if ($league) {
                    $validatedData['league_id'] = $league->id;
                }
            }

            Log::info('Validated data before creation:', $validatedData);

            // Create the tournament
            $tournament = Tournament::create($validatedData);

            // Load relationships for response
            $tournament->load(['club', 'league', 'sport']);

            Log::info('Tournament created successfully:', ['tournament_id' => $tournament->id]);

            return response()->json([
                'message' => 'Tournament created successfully',
                'tournament' => $tournament
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating tournament:', $e->errors());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating tournament: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Error creating tournament: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Tournament $tournament): JsonResponse
    {
        try {
            $tournament->load(['club', 'league', 'sport', 'participants']);
            return response()->json($tournament);
        } catch (\Exception $e) {
            Log::error('Error fetching tournament: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching tournament'], 500);
        }
    }

    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user can update this tournament
            if ($user->role === 'club_admin') {
                $club = Club::where('admin_id', $user->id)->first();
                if (!$club || $tournament->club_id !== $club->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            } elseif ($user->role === 'league_admin') {
                $league = League::where('admin_id', $user->id)->first();
                if (!$league || $tournament->league_id !== $league->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            // Base validation rules
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after:start_date',
                'registration_deadline' => 'sometimes|required|date',
                'max_participants' => 'sometimes|required|integer|min:2',
                'tournament_type' => 'sometimes|required|in:individual,team',
                'status' => 'sometimes|in:draft,published,in_progress,completed,cancelled',
            ];

            // Add conditional validation based on tournament type
            $tournamentType = $request->tournament_type ?? $tournament->tournament_type;
            
            if ($tournamentType === 'individual') {
                $rules = array_merge($rules, [
                    'modality' => 'nullable|in:singles,doubles',
                    'elimination_type' => 'nullable|in:simple_elimination,direct_elimination,round_robin,mixed',
                    'min_ranking' => 'nullable|integer|min:0',
                    'max_ranking' => 'nullable|integer|min:0',
                    'reminder_days' => 'nullable|in:7,15',
                    'first_prize' => 'nullable|string|max:500',
                    'second_prize' => 'nullable|string|max:500',
                    'third_prize' => 'nullable|string|max:500',
                    'fourth_prize' => 'nullable|string|max:500',
                    'fifth_prize' => 'nullable|string|max:500',
                    'contact_name' => 'nullable|string|max:255',
                    'contact_phone' => 'nullable|string|max:50',
                    'ball_info' => 'nullable|string|max:1000',
                ]);
            } elseif ($tournamentType === 'team') {
                $rules = array_merge($rules, [
                    'team_size' => 'nullable|integer|min:1',
                    'min_age' => 'nullable|integer|min:0',
                    'max_age' => 'nullable|integer|min:0',
                    'gender_restriction' => 'nullable|in:male,female,mixed',
                    'skill_level' => 'nullable|in:beginner,intermediate,advanced,professional',
                    'first_prize' => 'nullable|string|max:500',
                    'second_prize' => 'nullable|string|max:500',
                    'third_prize' => 'nullable|string|max:500',
                    'fourth_prize' => 'nullable|string|max:500',
                    'fifth_prize' => 'nullable|string|max:500',
                    'contact_name' => 'nullable|string|max:255',
                    'contact_phone' => 'nullable|string|max:50',
                    'ball_info' => 'nullable|string|max:1000',
                ]);
            }

            $validatedData = $request->validate($rules);

            $tournament->update($validatedData);
            $tournament->load(['club', 'league', 'sport']);

            return response()->json([
                'message' => 'Tournament updated successfully',
                'tournament' => $tournament
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tournament: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating tournament'], 500);
        }
    }

    public function destroy(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user can delete this tournament
            if ($user->role === 'club_admin') {
                $club = Club::where('admin_id', $user->id)->first();
                if (!$club || $tournament->club_id !== $club->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            } elseif ($user->role === 'league_admin') {
                $league = League::where('admin_id', $user->id)->first();
                if (!$league || $tournament->league_id !== $league->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $tournament->delete();

            return response()->json(['message' => 'Tournament deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting tournament: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting tournament'], 500);
        }
    }
}