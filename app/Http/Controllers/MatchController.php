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

            $matches = TournamentMatch::with([
                'participant1.member.club',
                'participant2.member.club',
                'winner.member.club'
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
     * Generate bracket for tournament
     */
    public function generateBracket(Tournament $tournament): JsonResponse
    {
        try {
            // Verificar permisos
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            if (!$this->canManageTournament($user, $tournament)) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            // Verificar si ya existe un bracket
            $existingMatches = TournamentMatch::where('tournament_id', $tournament->id)->count();
            if ($existingMatches > 0) {
                return response()->json([
                    'success' => false, 
                    'message' => 'El bracket ya ha sido generado para este torneo'
                ], 400);
            }

            // Obtener participantes
            $participants = TournamentParticipant::where('tournament_id', $tournament->id)
                ->where('status', 'registered')
                ->with(['member.user', 'member.club'])
                ->get();

            if ($participants->count() < 2) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Se necesitan al menos 2 participantes para generar el bracket'
                ], 400);
            }

            // Generar bracket según el formato del torneo
            $this->generateMatchesForFormat($tournament, $participants);

            return response()->json([
                'success' => true,
                'message' => 'Bracket generado exitosamente',
                'participants_count' => $participants->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno del servidor'
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
                'score' => 'nullable|string|max:255',
                'score_p1' => 'nullable|integer|min:0',
                'score_p2' => 'nullable|integer|min:0',
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
                $updateData = [
                    'winner_id' => $validatedData['winner_id'],
                    'status' => 'completed',
                    'completed_at' => now()
                ];

                if (isset($validatedData['score'])) {
                    $updateData['score'] = $validatedData['score'];
                }

                if (isset($validatedData['score_p1'])) {
                    $updateData['participant1_score'] = $validatedData['score_p1'];
                }

                if (isset($validatedData['score_p2'])) {
                    $updateData['participant2_score'] = $validatedData['score_p2'];
                }

                if (isset($validatedData['sets_data'])) {
                    $updateData['sets_data'] = json_encode($validatedData['sets_data']);
                }

                if (isset($validatedData['duration_minutes'])) {
                    $updateData['duration_minutes'] = $validatedData['duration_minutes'];
                }

                if (isset($validatedData['notes'])) {
                    $updateData['notes'] = $validatedData['notes'];
                }

                if (isset($validatedData['court_number'])) {
                    $updateData['court_number'] = $validatedData['court_number'];
                }

                if (isset($validatedData['referee'])) {
                    $updateData['referee'] = $validatedData['referee'];
                }

                $match->update($updateData);

                // Advance winner to next round
                $this->advanceWinner($match);

                // Check if tournament is completed
                $this->checkTournamentCompletion($tournament);

                DB::commit();

                // Load relationships for response
                $match->load([
                    'participant1.member.club',
                    'participant2.member.club',
                    'winner.member.club'
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
                'participant1.member.club',
                'participant2.member.club',
                'winner.member.club'
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
        
        // Calculate the number of rounds needed
        $rounds = ceil(log($participantCount, 2));
        
        // Calculate the next power of 2 to determine bracket size
        $bracketSize = pow(2, $rounds);
        
        Log::info("Generating bracket", [
            'participants' => $participantCount,
            'rounds' => $rounds,
            'bracket_size' => $bracketSize
        ]);

        // Shuffle participants for random seeding (or sort by seed if available)
        $shuffledParticipants = $participants->shuffle();
        
        $matches = collect();
        
        // Generate all rounds structure first
        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / pow(2, $round);
            
            for ($matchNum = 1; $matchNum <= $matchesInRound; $matchNum++) {
                $match = TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $round,
                    'match_number' => $matchNum,
                    'participant1_id' => null,
                    'participant2_id' => null,
                    'status' => 'scheduled',
                    'bracket_position' => $matchNum,
                    'is_bye' => false
                ]);
                
                $matches->push($match);
            }
        }
        
        // Now populate the first round with participants
        $firstRoundMatches = $matches->where('round', 1);
        $participantIndex = 0;
        
        foreach ($firstRoundMatches as $match) {
            $participant1 = $participantIndex < $participantCount ? $shuffledParticipants[$participantIndex] : null;
            $participantIndex++;
            $participant2 = $participantIndex < $participantCount ? $shuffledParticipants[$participantIndex] : null;
            $participantIndex++;
            
            // Update match with participants
            $updateData = [
                'participant1_id' => $participant1 ? $participant1->id : null,
                'participant2_id' => $participant2 ? $participant2->id : null,
            ];
            
            // Handle different scenarios
            if ($participant1 && $participant2) {
                // Normal match - both participants present
                $updateData['status'] = 'scheduled';
                $updateData['is_bye'] = false;
            } elseif ($participant1 && !$participant2) {
                // Bye match - only one participant
                $updateData['status'] = 'completed';
                $updateData['is_bye'] = true;
                $updateData['winner_id'] = $participant1->id;
                $updateData['score'] = 'Bye';
                $updateData['completed_at'] = now();
            } else {
                // Empty match - no participants (shouldn't happen in first round)
                $updateData['status'] = 'scheduled';
                $updateData['is_bye'] = false;
            }
            
            $match->update($updateData);
        }
        
        // Set up next_match_id relationships
        for ($round = 1; $round < $rounds; $round++) {
            $currentRoundMatches = $matches->where('round', $round)->sortBy('match_number');
            $nextRoundMatches = $matches->where('round', $round + 1)->sortBy('match_number');
            
            $nextMatchIndex = 0;
            $currentRoundMatches->chunk(2)->each(function ($matchPair) use ($nextRoundMatches, &$nextMatchIndex) {
                $nextMatch = $nextRoundMatches->values()[$nextMatchIndex] ?? null;
                
                if ($nextMatch) {
                    foreach ($matchPair as $match) {
                        $match->update(['next_match_id' => $nextMatch->id]);
                    }
                }
                
                $nextMatchIndex++;
            });
        }
        
        // Advance winners from bye matches to next round
        $this->advanceByeWinners($tournament);
        
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
                    'bracket_position' => $matchNumber,
                    'is_bye' => false
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
     * Advance winners from bye matches to the next round
     */
    private function advanceByeWinners(Tournament $tournament)
    {
        $byeMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('is_bye', true)
            ->where('status', 'completed')
            ->get();
            
        foreach ($byeMatches as $match) {
            $this->advanceWinner($match);
        }
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

        // Find all matches that feed into the next match
        $feedingMatches = TournamentMatch::where('next_match_id', $nextMatch->id)
            ->orderBy('match_number')
            ->get();
        
        // Find the position of the current match
        $matchPosition = $feedingMatches->search(function ($m) use ($match) {
            return $m->id === $match->id;
        });

        // Update the appropriate participant slot in the next match
        if ($matchPosition === 0) {
            // First match feeds into participant1
            $nextMatch->update(['participant1_id' => $match->winner_id]);
        } elseif ($matchPosition === 1) {
            // Second match feeds into participant2
            $nextMatch->update(['participant2_id' => $match->winner_id]);
        }
        
        // Check if the next match is ready to be played
        $nextMatch->refresh();
        if ($nextMatch->participant1_id && $nextMatch->participant2_id && $nextMatch->status === 'scheduled') {
            // Both participants are set, match is ready
            Log::info("Match {$nextMatch->id} is ready to play", [
                'participant1' => $nextMatch->participant1_id,
                'participant2' => $nextMatch->participant2_id
            ]);
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
            
            // Find the tournament winner (winner of the final match)
            $finalMatch = TournamentMatch::where('tournament_id', $tournament->id)
                ->orderBy('round', 'desc')
                ->first();
                
            if ($finalMatch && $finalMatch->winner_id) {
                Log::info("Tournament {$tournament->id} completed. Winner: {$finalMatch->winner_id}");
            }
        }

        // Update matches played count if the field exists
        if ($tournament->getConnection()->getSchemaBuilder()->hasColumn('tournaments', 'matches_played')) {
            $tournament->update(['matches_played' => $completedMatches]);
        }
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