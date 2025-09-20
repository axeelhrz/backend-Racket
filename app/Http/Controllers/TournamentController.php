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
            } elseif ($user->role === 'club') {
                // For club role, find tournaments for clubs owned by this user
                $query->whereHas('club', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $tournaments = $query->get();

            return response()->json([
                'success' => true,
                'data' => $tournaments
            ]);
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

            // Simplified validation rules focusing on required fields
            $rules = [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'registration_deadline' => 'required|date|before:start_date',
                'max_participants' => 'required|integer|min:2',
                'tournament_type' => 'required|in:individual,team',
                'status' => 'nullable|in:upcoming,active,completed,cancelled',
                'club_id' => 'nullable|exists:clubs,id',
                'league_id' => 'nullable|exists:leagues,id',
                'sport_id' => 'nullable|exists:sports,id',
                'entry_fee' => 'nullable|numeric|min:0',
                
                // Code validation - ensure uniqueness
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) {
                        if (Tournament::where('code', $value)->exists()) {
                            $fail('El código del torneo ya existe. Por favor, elige otro código.');
                        }
                    }
                ],
                
                // Location fields
                'country' => 'nullable|string|max:255',
                'province' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'club_name' => 'nullable|string|max:255',
                'club_address' => 'nullable|string|max:500',
                'image' => 'nullable|string',
                
                // Individual tournament fields
                'modality' => 'nullable|in:singles,doubles',
                'match_type' => 'nullable|string',
                'seeding_type' => 'nullable|string',
                'min_ranking' => 'nullable|string',
                'max_ranking' => 'nullable|string',
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
                'contact' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
            ];

            $validatedData = $request->validate($rules);

            // Set default values
            $validatedData['status'] = $validatedData['status'] ?? 'upcoming';
            $validatedData['entry_fee'] = $validatedData['entry_fee'] ?? 0;
            $validatedData['current_participants'] = 0;

            // Generate unique code if not provided or empty
            if (empty($validatedData['code'])) {
                $validatedData['code'] = $this->generateUniqueCode();
            }

            // Handle club_id based on user role
            if ($user->role === 'club' || $user->role === 'club_admin') {
                if (!isset($validatedData['club_id']) || !$validatedData['club_id']) {
                    $club = Club::where('user_id', $user->id)->first();
                    if ($club) {
                        $validatedData['club_id'] = $club->id;
                        
                        // If club has a league, associate the tournament with it
                        if ($club->league_id && !isset($validatedData['league_id'])) {
                            $validatedData['league_id'] = $club->league_id;
                        }
                    }
                }
            }

            // Handle league_id based on user role
            if ($user->role === 'league_admin') {
                if (!isset($validatedData['league_id']) || !$validatedData['league_id']) {
                    $league = League::where('admin_id', $user->id)->first();
                    if ($league) {
                        $validatedData['league_id'] = $league->id;
                    }
                }
            }

            // Ensure league_id is null if not set (to avoid NOT NULL constraint issues)
            if (!isset($validatedData['league_id']) || empty($validatedData['league_id'])) {
                $validatedData['league_id'] = null;
            }

            // Ensure sport_id is null if not set (to avoid NOT NULL constraint issues)
            if (!isset($validatedData['sport_id']) || empty($validatedData['sport_id'])) {
                $validatedData['sport_id'] = null;
            }

            // Set tournament format based on type
            if (!isset($validatedData['tournament_format'])) {
                $validatedData['tournament_format'] = 'single_elimination';
            }

            Log::info('Validated data before creation:', $validatedData);

            // Create the tournament
            $tournament = Tournament::create($validatedData);

            // Load relationships for response
            $tournament->load(['club', 'league', 'sport']);

            Log::info('Tournament created successfully:', ['tournament_id' => $tournament->id]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament created successfully',
                'data' => $tournament
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating tournament:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating tournament: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error creating tournament: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Tournament $tournament): JsonResponse
    {
        try {
            $tournament->load(['club', 'league', 'sport', 'participants']);
            return response()->json([
                'success' => true,
                'data' => $tournament
            ]);
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
            } elseif ($user->role === 'club') {
                $club = Club::where('user_id', $user->id)->first();
                if (!$club || $tournament->club_id !== $club->id) {
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
                'status' => 'sometimes|in:upcoming,active,completed,cancelled',
                'code' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) use ($tournament) {
                        if (Tournament::where('code', $value)->where('id', '!=', $tournament->id)->exists()) {
                            $fail('El código del torneo ya existe. Por favor, elige otro código.');
                        }
                    }
                ],
            ];

            $validatedData = $request->validate($rules);

            $tournament->update($validatedData);
            $tournament->load(['club', 'league', 'sport']);

            return response()->json([
                'success' => true,
                'message' => 'Tournament updated successfully',
                'data' => $tournament
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
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
            } elseif ($user->role === 'club') {
                $club = Club::where('user_id', $user->id)->first();
                if (!$club || $tournament->club_id !== $club->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $tournament->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tournament deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting tournament: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting tournament'], 500);
        }
    }

    /**
     * Generate a unique tournament code
     */
    private function generateUniqueCode(): string
    {
        do {
            // Generate a 6-digit random number as string
            $code = (string)rand(100000, 999999);
        } while (Tournament::where('code', $code)->exists());
        
        return $code;
    }
}