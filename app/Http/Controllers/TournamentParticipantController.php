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
            $participants = TournamentParticipant::with(['member.user', 'member.club'])
                ->where('tournament_id', $tournament->id)
                ->orderBy('registration_date', 'asc')
                ->get();

            // Transform the data to ensure consistent structure
            $transformedParticipants = $participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'tournament_id' => $participant->tournament_id,
                    'member_id' => $participant->member_id,
                    'status' => $participant->status,
                    'registration_date' => $participant->registration_date,
                    'seed' => $participant->seed,
                    'notes' => $participant->notes,
                    'member' => [
                        'id' => $participant->member->id,
                        'first_name' => $participant->member->first_name,
                        'last_name' => $participant->member->last_name,
                        'email' => $participant->member->email,
                        'phone' => $participant->member->phone,
                        'gender' => $participant->member->gender,
                        'ranking' => $participant->member->ranking,
                        'photo' => $participant->member->photo_path,
                        'club' => $participant->member->club ? [
                            'id' => $participant->member->club->id,
                            'name' => $participant->member->club->name,
                        ] : null,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedParticipants,
                'count' => $transformedParticipants->count(),
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

            // Verify member exists and get member info
            $member = Member::with(['user', 'club'])->find($validatedData['member_id']);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Miembro no encontrado'
                ], 404);
            }

            // Check tournament filters (age, gender, ranking, etc.)
            $filterCheck = $this->checkTournamentFilters($tournament, $member);
            if (!$filterCheck['eligible']) {
                return response()->json([
                    'success' => false,
                    'message' => $filterCheck['reason']
                ], 400);
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

            // Load relationships for response
            $participant->load(['member.user', 'member.club', 'tournament']);

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

            $participant->load(['member.user', 'member.club']);

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
                'tournament_club_id' => $tournament->club_id,
                'tournament_league_id' => $tournament->league_id
            ]);

            // Get members that are already registered in this tournament
            $registeredMemberIds = TournamentParticipant::where('tournament_id', $tournament->id)
                ->pluck('member_id')
                ->toArray();

            Log::info('Already registered members', ['count' => count($registeredMemberIds), 'ids' => $registeredMemberIds]);

            // Start building the query for available members
            $query = Member::with(['user', 'club'])
                ->where('status', 'active');

            // Filter by club context based on user role and tournament
            $userClub = null;
            $userLeague = null;

            if ($user->role === 'club') {
                // For club users, get their club
                $userClub = \App\Models\Club::where('user_id', $user->id)->first();
                Log::info('Club user detected', ['user_club_id' => $userClub?->id, 'user_club_name' => $userClub?->name]);
                
                if ($userClub) {
                    // Show only members from the user's club
                    $query->where('club_id', $userClub->id);
                    Log::info('Filtering members by user club', ['club_id' => $userClub->id]);
                } else {
                    Log::warning('Club user has no associated club');
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No se encontró club asociado al usuario'
                    ]);
                }
            } elseif ($user->role === 'club_admin') {
                // For club admins, get their administered club
                $userClub = \App\Models\Club::where('admin_id', $user->id)->first();
                Log::info('Club admin detected', ['admin_club_id' => $userClub?->id, 'admin_club_name' => $userClub?->name]);
                
                if ($userClub) {
                    // Show only members from the administered club
                    $query->where('club_id', $userClub->id);
                    Log::info('Filtering members by admin club', ['club_id' => $userClub->id]);
                } else {
                    Log::warning('Club admin has no associated club');
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No se encontró club administrado por el usuario'
                    ]);
                }
            } elseif ($user->role === 'liga' || $user->role === 'league_admin') {
                // For league admins, show members from clubs in their league
                $userLeague = \App\Models\League::where('admin_id', $user->id)->first();
                Log::info('League admin detected', ['league_id' => $userLeague?->id, 'league_name' => $userLeague?->name]);
                
                if ($userLeague) {
                    $query->whereHas('club', function ($q) use ($userLeague) {
                        $q->where('league_id', $userLeague->id);
                    });
                    Log::info('Filtering members by league clubs', ['league_id' => $userLeague->id]);
                } else {
                    Log::warning('League admin has no associated league');
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No se encontró liga administrada por el usuario'
                    ]);
                }
            } elseif ($user->role === 'super_admin') {
                // Super admin logic: prioritize tournament context
                if ($tournament->club_id) {
                    // If tournament belongs to a specific club, show members from that club
                    $query->where('club_id', $tournament->club_id);
                    Log::info('Super admin: filtering by tournament club', ['tournament_club_id' => $tournament->club_id]);
                } elseif ($tournament->league_id) {
                    // If tournament belongs to a league, show members from clubs in that league
                    $query->whereHas('club', function ($q) use ($tournament) {
                        $q->where('league_id', $tournament->league_id);
                    });
                    Log::info('Super admin: filtering by tournament league', ['tournament_league_id' => $tournament->league_id]);
                } else {
                    // If no specific context, show all active members
                    Log::info('Super admin: showing all active members');
                }
            } else {
                Log::warning('Unknown user role', ['role' => $user->role]);
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Rol de usuario no reconocido'
                ]);
            }

            // Exclude already registered members
            if (!empty($registeredMemberIds)) {
                $query->whereNotIn('id', $registeredMemberIds);
            }

            // Apply tournament-specific filters
            $filtersApplied = [];
            
            if ($tournament->tournament_type === 'individual') {
                // Apply individual tournament filters
                if ($tournament->age_filter && $tournament->min_age && $tournament->max_age) {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN ? AND ?', 
                        [$tournament->min_age, $tournament->max_age]);
                    $filtersApplied[] = "age: {$tournament->min_age}-{$tournament->max_age}";
                }

                if ($tournament->gender && $tournament->gender !== 'mixed') {
                    $query->where('gender', $tournament->gender);
                    $filtersApplied[] = "gender: {$tournament->gender}";
                }

                // Apply ranking filters if specified
                if ($tournament->ranking_filter && $tournament->min_ranking && $tournament->max_ranking) {
                    $query->whereBetween('ranking', [$tournament->min_ranking, $tournament->max_ranking]);
                    $filtersApplied[] = "ranking: {$tournament->min_ranking}-{$tournament->max_ranking}";
                }
            } else {
                // Apply team tournament filters
                if ($tournament->team_age_filter && $tournament->team_min_age && $tournament->team_max_age) {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN ? AND ?', 
                        [$tournament->team_min_age, $tournament->team_max_age]);
                    $filtersApplied[] = "team_age: {$tournament->team_min_age}-{$tournament->team_max_age}";
                }

                if ($tournament->team_gender && $tournament->team_gender !== 'mixed') {
                    $query->where('gender', $tournament->team_gender);
                    $filtersApplied[] = "team_gender: {$tournament->team_gender}";
                }

                // Apply team ranking filters if specified
                if ($tournament->team_ranking_filter && $tournament->team_min_ranking && $tournament->team_max_ranking) {
                    $query->whereBetween('ranking', [$tournament->team_min_ranking, $tournament->team_max_ranking]);
                    $filtersApplied[] = "team_ranking: {$tournament->team_min_ranking}-{$tournament->team_max_ranking}";
                }
            }

            Log::info('Tournament filters applied', ['filters' => $filtersApplied]);

            // Order by name for better UX
            $query->orderBy('first_name')->orderBy('last_name');

            $availableMembers = $query->get();

            Log::info('Available members query result', [
                'count' => $availableMembers->count(),
                'sample_members' => $availableMembers->take(3)->map(function($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name,
                        'club_id' => $member->club_id,
                        'club_name' => $member->club?->name
                    ];
                })
            ]);

            return response()->json([
                'success' => true,
                'data' => $availableMembers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available members: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener miembros disponibles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournament statistics and debug info
     */
    public function tournamentStats(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Get all members in the system for comparison
            $allMembersQuery = Member::with(['club']);
            
            // Filter by user context
            if ($user->role === 'club' || $user->role === 'club_admin') {
                $userClub = null;
                if ($user->role === 'club') {
                    $userClub = \App\Models\Club::where('user_id', $user->id)->first();
                } else {
                    $userClub = \App\Models\Club::where('admin_id', $user->id)->first();
                }

                if ($userClub) {
                    $allMembersQuery->where('club_id', $userClub->id);
                }
            } elseif ($user->role === 'liga' || $user->role === 'league_admin') {
                $userLeague = \App\Models\League::where('admin_id', $user->id)->first();
                if ($userLeague) {
                    $allMembersQuery->whereHas('club', function ($q) use ($userLeague) {
                        $q->where('league_id', $userLeague->id);
                    });
                }
            }

            $allMembers = $allMembersQuery->get();
            $activeMembers = $allMembers->where('status', 'active');

            // Get registered participants
            $participants = TournamentParticipant::with(['member.club'])
                ->where('tournament_id', $tournament->id)
                ->get();

            $registeredMemberIds = $participants->pluck('member_id')->toArray();

            // Apply tournament filters to see who would be eligible
            $eligibleMembers = $activeMembers->filter(function ($member) use ($tournament) {
                // Age filter
                if ($tournament->tournament_type === 'individual') {
                    if ($tournament->age_filter && $tournament->min_age && $tournament->max_age) {
                        $memberAge = $member->birth_date ? now()->diffInYears($member->birth_date) : null;
                        if ($memberAge && ($memberAge < $tournament->min_age || $memberAge > $tournament->max_age)) {
                            return false;
                        }
                    }

                    // Gender filter
                    if ($tournament->gender && $tournament->gender !== 'mixed') {
                        if ($member->gender !== $tournament->gender) {
                            return false;
                        }
                    }
                } else {
                    // Team tournament filters
                    if ($tournament->team_age_filter && $tournament->team_min_age && $tournament->team_max_age) {
                        $memberAge = $member->birth_date ? now()->diffInYears($member->birth_date) : null;
                        if ($memberAge && ($memberAge < $tournament->team_min_age || $memberAge > $tournament->team_max_age)) {
                            return false;
                        }
                    }

                    if ($tournament->team_gender && $tournament->team_gender !== 'mixed') {
                        if ($member->gender !== $tournament->team_gender) {
                            return false;
                        }
                    }
                }

                return true;
            });

            $availableMembers = $eligibleMembers->whereNotIn('id', $registeredMemberIds);

            // Get user's club info
            $userClubInfo = null;
            if ($user->role === 'club' || $user->role === 'club_admin') {
                if ($user->role === 'club') {
                    $userClubInfo = \App\Models\Club::where('user_id', $user->id)->first();
                } else {
                    $userClubInfo = \App\Models\Club::where('admin_id', $user->id)->first();
                }
            }

            $stats = [
                'tournament_info' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'type' => $tournament->tournament_type,
                    'max_participants' => $tournament->max_participants,
                    'current_participants' => $tournament->current_participants,
                    'club_id' => $tournament->club_id,
                    'league_id' => $tournament->league_id,
                ],
                'user_info' => [
                    'id' => $user->id,
                    'role' => $user->role,
                    'club_id' => $userClubInfo?->id,
                    'club_name' => $userClubInfo?->name,
                ],
                'member_counts' => [
                    'total_members_in_system' => $allMembers->count(),
                    'active_members_in_context' => $activeMembers->count(),
                    'eligible_members' => $eligibleMembers->count(),
                    'registered_participants' => $participants->count(),
                    'available_members' => $availableMembers->count(),
                ],
                'filters_applied' => [
                    'age_filter' => $tournament->age_filter ?? false,
                    'min_age' => $tournament->min_age,
                    'max_age' => $tournament->max_age,
                    'gender_filter' => $tournament->gender ?? 'none',
                    'ranking_filter' => $tournament->ranking_filter ?? false,
                    'min_ranking' => $tournament->min_ranking,
                    'max_ranking' => $tournament->max_ranking,
                ],
                'member_breakdown' => [
                    'by_status' => $allMembers->groupBy('status')->map->count(),
                    'by_gender' => $activeMembers->groupBy('gender')->map->count(),
                    'by_club' => $activeMembers->groupBy('club.name')->map->count(),
                ],
                'registered_participants' => $participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'member_name' => $participant->member->first_name . ' ' . $participant->member->last_name,
                        'member_email' => $participant->member->email,
                        'club_name' => $participant->member->club?->name,
                        'status' => $participant->status,
                        'registration_date' => $participant->registration_date,
                    ];
                }),
                'available_members_sample' => $availableMembers->take(10)->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->first_name . ' ' . $member->last_name,
                        'email' => $member->email,
                        'club_name' => $member->club?->name,
                        'gender' => $member->gender,
                        'ranking' => $member->ranking,
                        'status' => $member->status,
                    ];
                })->values(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament stats: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas del torneo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if member meets tournament filters
     */
    private function checkTournamentFilters(Tournament $tournament, Member $member): array
    {
        // Age filter check
        if ($tournament->tournament_type === 'individual') {
            if ($tournament->age_filter && $tournament->min_age && $tournament->max_age) {
                $memberAge = $member->birth_date ? now()->diffInYears($member->birth_date) : null;
                if ($memberAge && ($memberAge < $tournament->min_age || $memberAge > $tournament->max_age)) {
                    return [
                        'eligible' => false,
                        'reason' => "El participante no cumple con el rango de edad ({$tournament->min_age}-{$tournament->max_age} años)"
                    ];
                }
            }

            // Gender filter check
            if ($tournament->gender && $tournament->gender !== 'mixed') {
                if ($member->gender !== $tournament->gender) {
                    return [
                        'eligible' => false,
                        'reason' => 'El participante no cumple con el filtro de género del torneo'
                    ];
                }
            }
        } else {
            // Team tournament filters
            if ($tournament->team_age_filter && $tournament->team_min_age && $tournament->team_max_age) {
                $memberAge = $member->birth_date ? now()->diffInYears($member->birth_date) : null;
                if ($memberAge && ($memberAge < $tournament->team_min_age || $memberAge > $tournament->team_max_age)) {
                    return [
                        'eligible' => false,
                        'reason' => "El participante no cumple con el rango de edad ({$tournament->team_min_age}-{$tournament->team_max_age} años)"
                    ];
                }
            }

            // Team gender filter check
            if ($tournament->team_gender && $tournament->team_gender !== 'mixed') {
                if ($member->gender !== $tournament->team_gender) {
                    return [
                        'eligible' => false,
                        'reason' => 'El participante no cumple con el filtro de género del torneo'
                    ];
                }
            }
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * Update tournament participant count
     */
    private function updateTournamentParticipantCount(Tournament $tournament): void
    {
        $activeCount = TournamentParticipant::where('tournament_id', $tournament->id)
            ->whereIn('status', ['registered', 'confirmed'])
            ->count();

        $tournament->update(['current_participants' => $activeCount]);
    }
}