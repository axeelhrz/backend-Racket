<?php

namespace App\Http\Controllers;

use App\Models\TournamentMatch;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\Club;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchController extends Controller
{
    /**
     * Get all matches for a tournament
     */
    public function index(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            Log::info('Fetching matches for tournament: ' . $tournament->id);

            // Get matches with participants
            $matches = TournamentMatch::where('tournament_id', $tournament->id)
                ->with([
                    'participant1.member',
                    'participant2.member',
                    'winner.member'
                ])
                ->orderBy('round')
                ->orderBy('match_number')
                ->get();

            Log::info('Found matches: ' . $matches->count());

            return response()->json([
                'success' => true,
                'data' => $matches,
                'count' => $matches->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament matches: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching matches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bracket for tournament
     */
    public function generateBracket(Tournament $tournament): JsonResponse
    {
        try {
            Log::info('=== BRACKET GENERATION START ===');
            Log::info('Tournament ID: ' . $tournament->id);

            // Verificar permisos
            $user = Auth::user();
            if (!$user) {
                Log::error('No authenticated user');
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            Log::info('User authenticated: ' . $user->id . ' (' . $user->role . ')');

            // Basic permission check
            if (!in_array($user->role, ['super_admin', 'club', 'liga'])) {
                Log::error('User role not authorized: ' . $user->role);
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            // Get participants
            $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->with('member')
                ->get();

            Log::info('Active participants: ' . $participants->count());

            if ($participants->count() < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se necesitan al menos 2 participantes para generar el bracket. Encontrados: ' . $participants->count()
                ], 400);
            }

            // Check if matches already exist
            $existingMatches = TournamentMatch::where('tournament_id', $tournament->id)->count();
            
            if ($existingMatches > 0) {
                Log::info('Deleting existing matches: ' . $existingMatches);
                TournamentMatch::where('tournament_id', $tournament->id)->delete();
            }

            // Generate single elimination bracket
            $this->generateSingleEliminationBracket($tournament, $participants);

            Log::info('=== BRACKET GENERATION COMPLETE ===');

            return response()->json([
                'success' => true,
                'message' => 'Bracket generado exitosamente',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'participant_count' => $participants->count(),
                    'matches_created' => TournamentMatch::where('tournament_id', $tournament->id)->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('=== BRACKET GENERATION ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate single elimination bracket
     */
    private function generateSingleEliminationBracket(Tournament $tournament, $participants)
    {
        $participantCount = $participants->count();
        
        // Calculate number of rounds needed
        $totalRounds = ceil(log($participantCount, 2));
        
        // Calculate total matches needed
        $totalMatches = $participantCount - 1;
        
        Log::info("Generating bracket: {$participantCount} participants, {$totalRounds} rounds, {$totalMatches} matches");

        // Shuffle participants for random seeding
        $shuffledParticipants = $participants->shuffle();
        
        // Create first round matches
        $currentRoundParticipants = $shuffledParticipants->toArray();
        $matchNumber = 1;
        $allMatches = [];

        for ($round = 1; $round <= $totalRounds; $round++) {
            $roundMatches = [];
            $nextRoundParticipants = [];

            if ($round == 1) {
                // First round: pair up all participants
                for ($i = 0; $i < count($currentRoundParticipants); $i += 2) {
                    $participant1 = $currentRoundParticipants[$i];
                    $participant2 = isset($currentRoundParticipants[$i + 1]) ? $currentRoundParticipants[$i + 1] : null;

                    $match = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNumber++,
                        'participant1_id' => $participant1['id'],
                        'participant2_id' => $participant2 ? $participant2['id'] : null,
                        'status' => $participant2 ? 'scheduled' : 'bye',
                        'is_bye' => !$participant2,
                        'bracket_position' => count($roundMatches) + 1
                    ]);

                    if (!$participant2) {
                        // Bye match - participant1 automatically advances
                        $match->update([
                            'winner_id' => $participant1['id'],
                            'status' => 'completed',
                            'completed_at' => now()
                        ]);
                        $nextRoundParticipants[] = $participant1;
                    }

                    $roundMatches[] = $match;
                }
            } else {
                // Subsequent rounds: create matches for winners of previous round
                $previousRoundMatches = array_filter($allMatches, function($match) use ($round) {
                    return $match->round == ($round - 1);
                });

                for ($i = 0; $i < count($previousRoundMatches); $i += 2) {
                    $match1 = $previousRoundMatches[$i];
                    $match2 = isset($previousRoundMatches[$i + 1]) ? $previousRoundMatches[$i + 1] : null;

                    $match = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNumber++,
                        'participant1_id' => null, // Will be filled when previous matches complete
                        'participant2_id' => null, // Will be filled when previous matches complete
                        'status' => 'scheduled',
                        'bracket_position' => count($roundMatches) + 1
                    ]);

                    // Link previous matches to this match
                    $match1->update(['next_match_id' => $match->id]);
                    if ($match2) {
                        $match2->update(['next_match_id' => $match->id]);
                    }

                    $roundMatches[] = $match;
                }
            }

            $allMatches = array_merge($allMatches, $roundMatches);
            $currentRoundParticipants = $nextRoundParticipants;
        }

        Log::info("Created {$matchNumber} matches across {$totalRounds} rounds");
    }

    /**
     * Update match result
     */
    public function updateResult(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            $validatedData = $request->validate([
                'winner_id' => 'required|exists:tournament_participants,id',
                'score' => 'nullable|string',
                'score_p1' => 'nullable|integer|min:0',
                'score_p2' => 'nullable|integer|min:0',
                'notes' => 'nullable|string'
            ]);

            // Verify the winner is one of the participants
            if (!in_array($validatedData['winner_id'], [$match->participant1_id, $match->participant2_id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ganador debe ser uno de los participantes del partido'
                ], 400);
            }

            // Update match result
            $match->update([
                'winner_id' => $validatedData['winner_id'],
                'status' => 'completed',
                'completed_at' => now(),
                'score' => $validatedData['score'] ?? null,
                'participant1_score' => $validatedData['score_p1'] ?? null,
                'participant2_score' => $validatedData['score_p2'] ?? null,
                'notes' => $validatedData['notes'] ?? null
            ]);

            // Advance winner to next match if exists
            if ($match->next_match_id) {
                $nextMatch = TournamentMatch::find($match->next_match_id);
                if ($nextMatch) {
                    if (!$nextMatch->participant1_id) {
                        $nextMatch->update(['participant1_id' => $validatedData['winner_id']]);
                    } elseif (!$nextMatch->participant2_id) {
                        $nextMatch->update(['participant2_id' => $validatedData['winner_id']]);
                    }
                }
            }

            Log::info("Match {$match->id} result updated. Winner: {$validatedData['winner_id']}");

            return response()->json([
                'success' => true,
                'message' => 'Resultado actualizado exitosamente',
                'data' => $match->fresh(['participant1.member', 'participant2.member', 'winner.member'])
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating match result: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar resultado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournament bracket
     */
    public function getBracket(Tournament $tournament): JsonResponse
    {
        try {
            $matches = TournamentMatch::where('tournament_id', $tournament->id)
                ->with([
                    'participant1.member',
                    'participant2.member', 
                    'winner.member'
                ])
                ->orderBy('round')
                ->orderBy('bracket_position')
                ->get();

            $totalRounds = $matches->max('round') ?? 0;
            $completedMatches = $matches->where('status', 'completed')->count();
            $totalMatches = $matches->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'tournament' => $tournament,
                    'matches' => $matches,
                    'total_rounds' => $totalRounds,
                    'completed_matches' => $completedMatches,
                    'total_matches' => $totalMatches
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el bracket: ' . $e->getMessage()
            ], 500);
        }
    }
}