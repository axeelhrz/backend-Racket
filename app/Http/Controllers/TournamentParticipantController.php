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

            // Use Eloquent relationships to get participants with member data
            $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->with(['member.club'])
                ->orderBy('created_at', 'asc')
                ->get();

            Log::info('Found participants: ' . $participants->count());

            // Transform the data to match frontend expectations
            $transformedParticipants = $participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'tournament_id' => $participant->tournament_id,
                    'member_id' => $participant->member_id,
                    'status' => $participant->status ?? 'registered',
                    'registration_date' => $participant->registration_date ?? $participant->created_at,
                    'seed' => $participant->seed ?? null,
                    'notes' => $participant->notes ?? null,
                    'member' => [
                        'id' => $participant->member->id,
                        'first_name' => $participant->member->first_name,
                        'last_name' => $participant->member->last_name,
                        'email' => $participant->member->email,
                        'phone' => $participant->member->phone ?? '',
                        'photo' => $participant->member->photo ?? null,
                        'ranking' => $participant->member->ranking ?? null,
                        'gender' => $participant->member->gender ?? null,
                        'club' => $participant->member->club ? [
                            'id' => $participant->member->club->id,
                            'name' => $participant->member->club->name
                        ] : null
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedParticipants,
                'count' => $transformedParticipants->count(),
                'tournament_id' => $tournament->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in participants index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching participants: ' . $e->getMessage(),
                'data' => [],
                'count' => 0
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

            // Get registered member IDs
            $registeredMemberIds = TournamentParticipant::where('tournament_id', $tournament->id)
                ->pluck('member_id')
                ->toArray();

            Log::info('Registered member IDs: ' . implode(', ', $registeredMemberIds));

            // Build query for available members
            $query = Member::where('status', 'active')
                ->with('club');

            // Role-based filtering
            if ($user->role === 'club') {
                // Get user's club
                $userClub = DB::table('clubs')->where('user_id', $user->id)->first();
                if ($userClub) {
                    $query->where('club_id', $userClub->id);
                    Log::info('Filtering by club ID: ' . $userClub->id);
                } else {
                    Log::warning('No club found for user: ' . $user->id);
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No club found for user'
                    ]);
                }
            } elseif ($user->role === 'super_admin') {
                // Super admin can see all members, but filter by tournament club if available
                if ($tournament->club_id) {
                    $query->where('club_id', $tournament->club_id);
                    Log::info('Super admin filtering by tournament club ID: ' . $tournament->club_id);
                }
            }

            // Exclude already registered members
            if (!empty($registeredMemberIds)) {
                $query->whereNotIn('id', $registeredMemberIds);
            }

            // Apply tournament filters if they exist
            if ($tournament->gender && $tournament->gender !== 'mixed') {
                $query->where('gender', $tournament->gender);
                Log::info('Filtering by gender: ' . $tournament->gender);
            }

            if ($tournament->age_filter && $tournament->min_age && $tournament->max_age) {
                $query->whereBetween('age', [$tournament->min_age, $tournament->max_age]);
                Log::info('Filtering by age: ' . $tournament->min_age . '-' . $tournament->max_age);
            }

            if ($tournament->ranking_filter && $tournament->min_ranking && $tournament->max_ranking) {
                $query->whereBetween('ranking', [$tournament->min_ranking, $tournament->max_ranking]);
                Log::info('Filtering by ranking: ' . $tournament->min_ranking . '-' . $tournament->max_ranking);
            }

            $members = $query->orderBy('first_name')->get();

            Log::info('Found available members: ' . $members->count());

            // Transform the data
            $transformedMembers = $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'first_name' => $member->first_name ?? '',
                    'last_name' => $member->last_name ?? '',
                    'email' => $member->email ?? '',
                    'phone' => $member->phone ?? '',
                    'photo' => $member->photo ?? null,
                    'gender' => $member->gender ?? null,
                    'ranking' => $member->ranking ?? null,
                    'status' => $member->status ?? 'active',
                    'club' => $member->club ? [
                        'id' => $member->club->id,
                        'name' => $member->club->name
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedMembers
            ]);

        } catch (\Exception $e) {
            Log::error('Error in availableMembers: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available members: ' . $e->getMessage(),
                'data' => []
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
                'member_id' => 'required|integer|exists:members,id',
                'status' => 'nullable|in:registered,confirmed,withdrawn,disqualified',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Check if member is already registered
            $existingParticipant = TournamentParticipant::where('tournament_id', $tournament->id)
                ->where('member_id', $validatedData['member_id'])
                ->first();

            if ($existingParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este miembro ya está registrado en el torneo'
                ], 400);
            }

            // Check tournament capacity
            $currentParticipants = TournamentParticipant::where('tournament_id', $tournament->id)->count();
            if ($tournament->max_participants && $currentParticipants >= $tournament->max_participants) {
                return response()->json([
                    'success' => false,
                    'message' => 'El torneo ha alcanzado su capacidad máxima'
                ], 400);
            }

            // Create participant
            $participant = TournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'member_id' => $validatedData['member_id'],
                'registration_date' => now(),
                'status' => $validatedData['status'] ?? 'registered',
                'notes' => $validatedData['notes'] ?? null,
            ]);

            // Load the participant with member data
            $participant->load('member.club');

            Log::info('Participant created: ' . $participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Participante agregado exitosamente',
                'data' => [
                    'id' => $participant->id,
                    'tournament_id' => $participant->tournament_id,
                    'member_id' => $participant->member_id,
                    'status' => $participant->status,
                    'registration_date' => $participant->registration_date,
                    'member' => [
                        'id' => $participant->member->id,
                        'first_name' => $participant->member->first_name,
                        'last_name' => $participant->member->last_name,
                        'email' => $participant->member->email,
                        'club' => $participant->member->club ? [
                            'name' => $participant->member->club->name
                        ] : null
                    ]
                ]
            ], 201);

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
                'message' => 'Error al agregar participante: ' . $e->getMessage()
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

            $participant->update($validatedData);

            Log::info('Participant updated: ' . $participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Participante actualizado exitosamente',
                'data' => $participant->fresh(['member.club'])
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
                'message' => 'Error al actualizar participante: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove participant from tournament
     */
    public function destroy(Tournament $tournament, TournamentParticipant $participant): JsonResponse
    {
        try {
            // Check if tournament has started (has matches)
            $hasMatches = DB::table('matches')->where('tournament_id', $tournament->id)->exists();
            
            if ($hasMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar participantes después de que el torneo haya comenzado'
                ], 400);
            }

            $participant->delete();

            Log::info('Participant deleted: ' . $participant->id);

            return response()->json([
                'success' => true,
                'message' => 'Participante eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting participant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar participante: ' . $e->getMessage()
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

            // Get participant counts
            $participantCount = TournamentParticipant::where('tournament_id', $tournament->id)->count();
            $activeParticipantCount = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->count();

            // Get match counts
            $totalMatches = DB::table('matches')->where('tournament_id', $tournament->id)->count();
            $completedMatches = DB::table('matches')
                ->where('tournament_id', $tournament->id)
                ->where('status', 'completed')
                ->count();

            $stats = [
                'tournament_info' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'type' => $tournament->tournament_type,
                    'max_participants' => $tournament->max_participants,
                    'current_participants' => $activeParticipantCount,
                ],
                'participant_counts' => [
                    'total_registered' => $participantCount,
                    'active_participants' => $activeParticipantCount,
                    'available_slots' => max(0, ($tournament->max_participants ?? 0) - $activeParticipantCount),
                ],
                'match_counts' => [
                    'total_matches' => $totalMatches,
                    'completed_matches' => $completedMatches,
                    'pending_matches' => $totalMatches - $completedMatches,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del torneo: ' . $e->getMessage()
            ], 500);
        }
    }
}