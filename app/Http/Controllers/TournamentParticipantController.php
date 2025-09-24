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
            Log::info('TournamentParticipantController@index - Fetching participants for tournament: ' . $tournament->id);

            // Simple query without complex relationships first
            $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->orderBy('registration_date', 'asc')
                ->get();

            Log::info('TournamentParticipantController@index - Found participants: ' . $participants->count());

            // Try to load relationships safely
            $transformedParticipants = [];
            foreach ($participants as $participant) {
                try {
                    $member = Member::find($participant->member_id);
                    $club = null;
                    
                    if ($member && $member->club_id) {
                        $club = \App\Models\Club::find($member->club_id);
                    }

                    $transformedParticipants[] = [
                        'id' => $participant->id,
                        'tournament_id' => $participant->tournament_id,
                        'member_id' => $participant->member_id,
                        'status' => $participant->status ?? 'registered',
                        'registration_date' => $participant->registration_date ?? $participant->created_at,
                        'seed' => $participant->seed ?? null,
                        'notes' => $participant->notes ?? null,
                        'member' => $member ? [
                            'id' => $member->id,
                            'first_name' => $member->first_name ?? '',
                            'last_name' => $member->last_name ?? '',
                            'email' => $member->email ?? '',
                            'phone' => $member->phone ?? '',
                            'gender' => $member->gender ?? null,
                            'ranking' => $member->ranking ?? null,
                            'photo' => $member->photo_path ?? null,
                            'club' => $club ? [
                                'id' => $club->id,
                                'name' => $club->name,
                            ] : null,
                        ] : null,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error loading participant details: ' . $e->getMessage());
                    // Add participant with minimal data
                    $transformedParticipants[] = [
                        'id' => $participant->id,
                        'tournament_id' => $participant->tournament_id,
                        'member_id' => $participant->member_id,
                        'status' => $participant->status ?? 'registered',
                        'registration_date' => $participant->registration_date ?? $participant->created_at,
                        'seed' => $participant->seed ?? null,
                        'notes' => $participant->notes ?? null,
                        'member' => null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $transformedParticipants,
                'count' => count($transformedParticipants),
                'tournament_id' => $tournament->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tournament participants: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching participants',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
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
                'member_id' => 'required|exists:members,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Check if tournament is still accepting registrations
            if ($tournament->registration_deadline < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El período de inscripciones ha cerrado'
                ], 400);
            }

            // Check if tournament is full
            $currentParticipants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->count();

            if ($currentParticipants >= $tournament->max_participants) {
                return response()->json([
                    'success' => false,
                    'message' => 'El torneo ha alcanzado el máximo de participantes'
                ], 400);
            }

            // Check if member is already registered
            $existingParticipant = TournamentParticipant::where('tournament_id', $tournament->id)
                ->where('member_id', $validatedData['member_id'])
                ->first();

            if ($existingParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'El miembro ya está registrado en este torneo'
                ], 400);
            }

            // Verify member exists
            $member = Member::find($validatedData['member_id']);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Miembro no encontrado'
                ], 404);
            }

            DB::beginTransaction();

            // Create participant record
            $participant = TournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'member_id' => $validatedData['member_id'],
                'registration_date' => now(),
                'status' => 'registered',
                'notes' => $validatedData['notes'] ?? null
            ]);

            // Update tournament participant count
            $this->updateTournamentParticipantCount($tournament);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participante agregado exitosamente',
                'data' => $participant
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding tournament participant: ' . $e->getMessage());
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

            $oldStatus = $participant->status;
            $participant->update($validatedData);

            // Update tournament participant count if status changed
            if ($oldStatus !== $validatedData['status']) {
                $this->updateTournamentParticipantCount($tournament);
            }

            return response()->json([
                'success' => true,
                'message' => 'Participante actualizado exitosamente',
                'data' => $participant
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tournament participant: ' . $e->getMessage());
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
            DB::beginTransaction();

            $participant->delete();

            // Update tournament participant count
            $this->updateTournamentParticipantCount($tournament);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participante eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error removing tournament participant: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar participante'
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

            Log::info('Fetching available members for tournament', [
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);

            // Get members that are already registered in this tournament
            $registeredMemberIds = TournamentParticipant::where('tournament_id', $tournament->id)
                ->pluck('member_id')
                ->toArray();

            Log::info('Already registered members', ['count' => count($registeredMemberIds)]);

            // Start building the query for available members
            $query = Member::where('status', 'active');

            // Simple role-based filtering
            if ($user->role === 'club') {
                // For club users, get their club
                $userClub = \App\Models\Club::where('user_id', $user->id)->first();
                if ($userClub) {
                    $query->where('club_id', $userClub->id);
                } else {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No se encontró club asociado al usuario'
                    ]);
                }
            } elseif ($user->role === 'super_admin') {
                // Super admin can see all members, but prioritize tournament context
                if ($tournament->club_id) {
                    $query->where('club_id', $tournament->club_id);
                }
            }

            // Exclude already registered members
            if (!empty($registeredMemberIds)) {
                $query->whereNotIn('id', $registeredMemberIds);
            }

            // Order by name for better UX
            $query->orderBy('first_name')->orderBy('last_name');

            $availableMembers = $query->get();

            // Transform the data safely
            $transformedMembers = [];
            foreach ($availableMembers as $member) {
                try {
                    $club = null;
                    if ($member->club_id) {
                        $club = \App\Models\Club::find($member->club_id);
                    }

                    $transformedMembers[] = [
                        'id' => $member->id,
                        'first_name' => $member->first_name ?? '',
                        'last_name' => $member->last_name ?? '',
                        'email' => $member->email ?? '',
                        'phone' => $member->phone ?? '',
                        'gender' => $member->gender ?? null,
                        'ranking' => $member->ranking ?? null,
                        'status' => $member->status ?? 'active',
                        'club' => $club ? [
                            'id' => $club->id,
                            'name' => $club->name,
                        ] : null,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Error loading member details: ' . $e->getMessage());
                    // Add member with minimal data
                    $transformedMembers[] = [
                        'id' => $member->id,
                        'first_name' => $member->first_name ?? '',
                        'last_name' => $member->last_name ?? '',
                        'email' => $member->email ?? '',
                        'phone' => $member->phone ?? '',
                        'gender' => $member->gender ?? null,
                        'ranking' => $member->ranking ?? null,
                        'status' => $member->status ?? 'active',
                        'club' => null,
                    ];
                }
            }

            Log::info('Available members query result', ['count' => count($transformedMembers)]);

            return response()->json([
                'success' => true,
                'data' => $transformedMembers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available members: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener miembros disponibles',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
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

            // Get basic participant count
            $participantCount = TournamentParticipant::where('tournament_id', $tournament->id)->count();
            $activeParticipantCount = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->count();

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

    /**
     * Update tournament participant count
     */
    private function updateTournamentParticipantCount(Tournament $tournament): void
    {
        try {
            $activeCount = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->count();

            $tournament->update(['current_participants' => $activeCount]);
        } catch (\Exception $e) {
            Log::error('Error updating tournament participant count: ' . $e->getMessage());
        }
    }
}