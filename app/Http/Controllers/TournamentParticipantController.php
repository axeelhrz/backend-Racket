<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TournamentParticipantController extends Controller
{
    /**
     * Get all participants for a tournament
     */
    public function index(Tournament $tournament): JsonResponse
    {
        try {
            Log::info('TournamentParticipantController@index - Tournament ID: ' . $tournament->id);

            // Check if tournament_participants table exists
            try {
                $tableExists = DB::select("SHOW TABLES LIKE 'tournament_participants'");
                if (empty($tableExists)) {
                    Log::warning('tournament_participants table does not exist');
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'count' => 0,
                        'tournament_id' => $tournament->id,
                        'message' => 'No participants table found'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error checking table existence: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'count' => 0,
                    'tournament_id' => $tournament->id,
                    'message' => 'Database error'
                ]);
            }

            // Simple query to get participants
            try {
                $participants = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->orderBy('created_at', 'asc')
                    ->get();

                Log::info('Found participants: ' . $participants->count());

                $transformedParticipants = [];
                foreach ($participants as $participant) {
                    $transformedParticipants[] = [
                        'id' => $participant->id,
                        'tournament_id' => $participant->tournament_id,
                        'member_id' => $participant->member_id,
                        'status' => $participant->status ?? 'registered',
                        'registration_date' => $participant->registration_date ?? $participant->created_at,
                        'seed' => $participant->seed ?? null,
                        'notes' => $participant->notes ?? null,
                        'member' => null, // We'll load this separately if needed
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $transformedParticipants,
                    'count' => count($transformedParticipants),
                    'tournament_id' => $tournament->id,
                ]);

            } catch (\Exception $e) {
                Log::error('Error querying participants: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'count' => 0,
                    'tournament_id' => $tournament->id,
                    'message' => 'Query error: ' . $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in participants index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching participants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available members for tournament registration
     */
    public function availableMembers(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            Log::info('Fetching available members for tournament: ' . $tournament->id);

            // Check if members table exists
            try {
                $tableExists = DB::select("SHOW TABLES LIKE 'members'");
                if (empty($tableExists)) {
                    Log::warning('members table does not exist');
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No members table found'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error checking members table: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Database error'
                ]);
            }

            // Get registered member IDs (if tournament_participants table exists)
            $registeredMemberIds = [];
            try {
                $registeredMemberIds = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->pluck('member_id')
                    ->toArray();
            } catch (\Exception $e) {
                Log::warning('Could not get registered members: ' . $e->getMessage());
            }

            // Simple query to get members
            try {
                $query = DB::table('members')
                    ->where('status', 'active');

                // Simple role-based filtering
                if ($user->role === 'club') {
                    // Try to get user's club
                    try {
                        $userClub = DB::table('clubs')->where('user_id', $user->id)->first();
                        if ($userClub) {
                            $query->where('club_id', $userClub->id);
                        } else {
                            return response()->json([
                                'success' => true,
                                'data' => [],
                                'message' => 'No club found for user'
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Could not get user club: ' . $e->getMessage());
                    }
                } elseif ($user->role === 'super_admin') {
                    // Super admin can see all members, but filter by tournament club if available
                    if ($tournament->club_id) {
                        $query->where('club_id', $tournament->club_id);
                    }
                }

                // Exclude registered members
                if (!empty($registeredMemberIds)) {
                    $query->whereNotIn('id', $registeredMemberIds);
                }

                $members = $query->orderBy('first_name')->get();

                Log::info('Found available members: ' . $members->count());

                $transformedMembers = [];
                foreach ($members as $member) {
                    $transformedMembers[] = [
                        'id' => $member->id,
                        'first_name' => $member->first_name ?? '',
                        'last_name' => $member->last_name ?? '',
                        'email' => $member->email ?? '',
                        'phone' => $member->phone ?? '',
                        'gender' => $member->gender ?? null,
                        'ranking' => $member->ranking ?? null,
                        'status' => $member->status ?? 'active',
                        'club' => null, // We'll load this separately if needed
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $transformedMembers
                ]);

            } catch (\Exception $e) {
                Log::error('Error querying members: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Query error: ' . $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in availableMembers: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a participant to a tournament
     */
    public function store(Request $request, Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validatedData = $request->validate([
                'member_id' => 'required|integer',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Simple insert using DB query builder
            try {
                $participantId = DB::table('tournament_participants')->insertGetId([
                    'tournament_id' => $tournament->id,
                    'member_id' => $validatedData['member_id'],
                    'registration_date' => now(),
                    'status' => 'registered',
                    'notes' => $validatedData['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Participante agregado exitosamente',
                    'data' => [
                        'id' => $participantId,
                        'tournament_id' => $tournament->id,
                        'member_id' => $validatedData['member_id'],
                        'status' => 'registered'
                    ]
                ], 201);

            } catch (\Exception $e) {
                Log::error('Error inserting participant: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error al agregar participante: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar participante'
            ], 500);
        }
    }

    /**
     * Update participant status
     */
    public function update(Request $request, Tournament $tournament, TournamentParticipant $participant): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:registered,confirmed,withdrawn,disqualified',
                'seed' => 'nullable|integer|min:1',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Simple update using DB query builder
            DB::table('tournament_participants')
                ->where('id', $participant->id)
                ->update([
                    'status' => $validatedData['status'],
                    'seed' => $validatedData['seed'] ?? null,
                    'notes' => $validatedData['notes'] ?? null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Participante actualizado exitosamente',
                'data' => array_merge(['id' => $participant->id], $validatedData)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating participant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar participante'
            ], 500);
        }
    }

    /**
     * Remove participant from tournament
     */
    public function destroy(Tournament $tournament, TournamentParticipant $participant): JsonResponse
    {
        try {
            DB::table('tournament_participants')
                ->where('id', $participant->id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Participante eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting participant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar participante'
            ], 500);
        }
    }

    /**
     * Get tournament statistics
     */
    public function tournamentStats(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Get basic participant count using raw queries
            $participantCount = 0;
            $activeParticipantCount = 0;

            try {
                $participantCount = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->count();

                $activeParticipantCount = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->whereIn('status', ['registered', 'confirmed'])
                    ->count();
            } catch (\Exception $e) {
                Log::warning('Could not get participant counts: ' . $e->getMessage());
            }

            $stats = [
                'tournament_info' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'type' => $tournament->tournament_type,
                    'max_participants' => $tournament->max_participants,
                    'current_participants' => $tournament->current_participants,
                ],
                'participant_counts' => [
                    'total_registered' => $participantCount,
                    'active_participants' => $activeParticipantCount,
                    'available_slots' => max(0, $tournament->max_participants - $activeParticipantCount),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del torneo'
            ], 500);
        }
    }
}