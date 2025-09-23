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

            return response()->json([
                'success' => true,
                'data' => $participants
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tournament participants: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching participants'
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
            $tournament->increment('current_participants');

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
            // Get members that are not already registered in this tournament
            $registeredMemberIds = TournamentParticipant::where('tournament_id', $tournament->id)
                ->pluck('member_id');

            $query = Member::with(['user', 'club'])
                ->whereNotIn('id', $registeredMemberIds)
                ->where('status', 'active');

            // Apply tournament filters
            if ($tournament->tournament_type === 'individual') {
                // Apply individual tournament filters
                if ($tournament->age_filter && $tournament->min_age && $tournament->max_age) {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN ? AND ?', 
                        [$tournament->min_age, $tournament->max_age]);
                }

                if ($tournament->gender && $tournament->gender !== 'mixed') {
                    $query->where('gender', $tournament->gender);
                }
            } else {
                // Apply team tournament filters
                if ($tournament->team_age_filter && $tournament->team_min_age && $tournament->team_max_age) {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN ? AND ?', 
                        [$tournament->team_min_age, $tournament->team_max_age]);
                }

                if ($tournament->team_gender && $tournament->team_gender !== 'mixed') {
                    $query->where('gender', $tournament->team_gender);
                }
            }

            $availableMembers = $query->get();

            return response()->json([
                'success' => true,
                'data' => $availableMembers
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available members: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener miembros disponibles'
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