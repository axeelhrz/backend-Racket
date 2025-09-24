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
            $matches = TournamentMatch::with([
                'participant1.member',
                'participant2.member',
                'winner.member',
                'nextMatch'
            ])
            ->where('tournament_id', $tournament->id)
            ->orderBy('round')
            ->orderBy('match_number')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $matches
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tournament matches: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching matches'
            ], 500);
        }
    }

    /**
     * Generate bracket for a tournament
     */
    public function generateBracket(Tournament $tournament): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Check if user can manage this tournament
            if (!$this->canManageTournament($user, $tournament)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check if bracket already exists
            $existingMatches = TournamentMatch::where('tournament_id', $tournament->id)->count();
            if ($existingMatches > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El bracket ya ha sido generado para este torneo'
                ], 400);
            }

            // Get confirmed participants
            $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->with('member')
                ->get();

            if ($participants->count() < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se necesitan al menos 2 participantes para generar el bracket'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Generate matches based on tournament format
                $matches = $this->generateMatchesForFormat($tournament, $participants);

                // Update tournament status
                $tournament->update(['status' => 'active']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Bracket generado exitosamente',
                    'data' => $matches
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error generating tournament bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el bracket'
            ], 500);
        }
    }

    /**
     * Update match result
     */
    public function updateResult(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            if (!$this->canManageTournament($user, $tournament)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'winner_id' => 'required|exists:tournament_participants,id',
                'score' => 'required|string|max:255',
                'sets_data' => 'nullable|array',
                'duration_minutes' => 'nullable|integer|min:1',
                'notes' => 'nullable|string|max:1000',
                'court_number' => 'nullable|integer|min:1',
                'referee' => 'nullable|string|max:255'
            ]);

            // Validate winner is one of the participants
            if (!in_array($validatedData['winner_id'], [$match->participant1_id, $match->participant2_id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ganador debe ser uno de los participantes del partido'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Update match with result
                $match->update([
                    'winner_id' => $validatedData['winner_id'],
                    'score' => $validatedData['score'],
                    'sets_data' => $validatedData['sets_data'] ?? null,
                    'duration_minutes' => $validatedData['duration_minutes'] ?? null,
                    'notes' => $validatedData['notes'] ?? null,
                    'court_number' => $validatedData['court_number'] ?? null,
                    'referee' => $validatedData['referee'] ?? null,
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                // Advance winner to next round
                $this->advanceWinner($match);

                // Check if tournament is completed
                $this->checkTournamentCompletion($tournament);

                DB::commit();

                // Load relationships for response
                $match->load([
                    'participant1.member',
                    'participant2.member',
                    'winner.member',
                    'nextMatch'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Resultado actualizado exitosamente',
                    'data' => $match
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

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
                'message' => 'Error al actualizar el resultado'
            ], 500);
        }
    }

    /**
     * Get tournament bracket
     */
    public function getBracket(Tournament $tournament): JsonResponse
    {
        try {
            $matches = TournamentMatch::with([
                'participant1.member',
                'participant2.member',
                'winner.member'
            ])
            ->where('tournament_id', $tournament->id)
            ->orderBy('round')
            ->orderBy('bracket_position')
            ->get();

            $bracket = $matches->groupBy('round')->map(function ($roundMatches) {
                return $roundMatches->values();
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'tournament' => $tournament,
                    'bracket' => $bracket,
                    'total_rounds' => $matches->max('round') ?? 0,
                    'completed_matches' => $matches->where('status', 'completed')->count(),
                    'total_matches' => $matches->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el bracket'
            ], 500);
        }
    }

    /**
     * Generate matches based on tournament format
     */
    private function generateMatchesForFormat(Tournament $tournament, $participants)
    {
        switch ($tournament->tournament_format) {
            case 'single_elimination':
                return $this->generateSingleEliminationMatches($tournament, $participants);
            case 'double_elimination':
                return $this->generateDoubleEliminationMatches($tournament, $participants);
            case 'round_robin':
                return $this->generateRoundRobinMatches($tournament, $participants);
            default:
                return $this->generateSingleEliminationMatches($tournament, $participants);
        }
    }

    /**
     * Generate single elimination bracket
     */
    private function generateSingleEliminationMatches(Tournament $tournament, $participants)
    {
        $participantCount = $participants->count();
        $rounds = ceil(log($participantCount, 2));
        $totalSlots = pow(2, $rounds);
        
        // Sort participants by seed (or shuffle for random seeding)
        $shuffledParticipants = $participants->sortBy('seed')->values();
        
        $matches = collect();
        $currentRoundParticipants = $shuffledParticipants->pad($totalSlots, null);

        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $totalSlots / pow(2, $round);
            $roundMatches = collect();

            for ($matchNum = 1; $matchNum <= $matchesInRound; $matchNum++) {
                if ($round === 1) {
                    // First round - pair up participants
                    $participant1 = $currentRoundParticipants->shift();
                    $participant2 = $currentRoundParticipants->shift();
                    
                    $match = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNum,
                        'participant1_id' => $participant1 ? $participant1->id : null,
                        'participant2_id' => $participant2 ? $participant2->id : null,
                        'status' => ($participant1 && $participant2) ? 'scheduled' : 'bye',
                        'bracket_position' => $matchNum,
                        'is_bye' => !($participant1 && $participant2)
                    ]);

                    // Handle bye matches
                    if (!$participant2 && $participant1) {
                        $match->update([
                            'winner_id' => $participant1->id,
                            'status' => 'completed',
                            'score' => 'Bye',
                            'completed_at' => now()
                        ]);
                    }
                } else {
                    // Subsequent rounds - create empty matches
                    $match = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNum,
                        'status' => 'scheduled',
                        'bracket_position' => $matchNum
                    ]);
                }

                $roundMatches->push($match);
            }

            // Link matches to next round
            if ($round < $rounds) {
                $roundMatches->chunk(2)->each(function ($matchPair, $index) use ($round, $tournament) {
                    $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
                        ->where('round', $round + 1)
                        ->where('match_number', $index + 1)
                        ->first();

                    if ($nextMatch) {
                        $matchPair->each(function ($match) use ($nextMatch) {
                            $match->update(['next_match_id' => $nextMatch->id]);
                        });
                    }
                });
            }

            $matches = $matches->merge($roundMatches);
        }

        return $matches;
    }

    /**
     * Generate round robin matches
     */
    private function generateRoundRobinMatches(Tournament $tournament, $participants)
    {
        $matches = collect();
        $participantList = $participants->values();
        $matchNumber = 1;

        // Create matches for every participant combination
        for ($i = 0; $i < $participantList->count(); $i++) {
            for ($j = $i + 1; $j < $participantList->count(); $j++) {
                $match = TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => 1,
                    'match_number' => $matchNumber,
                    'participant1_id' => $participantList[$i]->id,
                    'participant2_id' => $participantList[$j]->id,
                    'status' => 'scheduled',
                    'bracket_position' => $matchNumber
                ]);

                $matches->push($match);
                $matchNumber++;
            }
        }

        return $matches;
    }

    /**
     * Generate double elimination matches (simplified version)
     */
    private function generateDoubleEliminationMatches(Tournament $tournament, $participants)
    {
        // For now, use single elimination logic
        // TODO: Implement proper double elimination bracket
        return $this->generateSingleEliminationMatches($tournament, $participants);
    }

    /**
     * Advance winner to next match
     */
    private function advanceWinner(TournamentMatch $match)
    {
        if (!$match->next_match_id || !$match->winner_id) {
            return;
        }

        $nextMatch = TournamentMatch::find($match->next_match_id);
        if (!$nextMatch) {
            return;
        }

        // Find which position this match feeds into
        $previousMatches = TournamentMatch::where('next_match_id', $nextMatch->id)->get();
        $matchIndex = $previousMatches->search(function ($m) use ($match) {
            return $m->id === $match->id;
        });

        // Update next match with winner
        if ($matchIndex === 0) {
            $nextMatch->update(['participant1_id' => $match->winner_id]);
        } elseif ($matchIndex === 1) {
            $nextMatch->update(['participant2_id' => $match->winner_id]);
        }
    }

    /**
     * Check if tournament is completed
     */
    private function checkTournamentCompletion(Tournament $tournament)
    {
        $totalMatches = TournamentMatch::where('tournament_id', $tournament->id)->count();
        $completedMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('status', 'completed')
            ->count();

        // Update tournament status if all matches are completed
        if ($totalMatches > 0 && $totalMatches === $completedMatches) {
            $tournament->update(['status' => 'completed']);
        }

        // Update matches played count
        $tournament->update(['matches_played' => $completedMatches]);
    }

    /**
     * Check if user can manage tournament
     */
    private function canManageTournament($user, Tournament $tournament): bool
    {
        // Super admin can manage all tournaments
        if ($user->role === 'super_admin') {
            return true;
        }

        // Club admin can manage their club's tournaments
        if ($user->role === 'club' && $tournament->club_id) {
            $userClub = Club::where('user_id', $user->id)->first();
            return $userClub && $userClub->id === $tournament->club_id;
        }

        // League admin can manage their league's tournaments
        if ($user->role === 'liga' && $tournament->league_id) {
            $userLeague = League::where('admin_id', $user->id)->first();
            return $userLeague && $userLeague->id === $tournament->league_id;
        }

        return false;
    }
}