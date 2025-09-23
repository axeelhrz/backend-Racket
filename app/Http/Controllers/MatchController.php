<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    public function index(Tournament $tournament): JsonResponse
    {
        try {
            $matches = \App\Models\Match::with([
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
            $existingMatches = \App\Models\Match::where('tournament_id', $tournament->id)->count();
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
            Log::error('Error generating tournament bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el bracket'
            ], 500);
        }
    }

    public function updateResult(Request $request, Tournament $tournament, \App\Models\Match $gameMatch): JsonResponse
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

            if (!in_array($validatedData['winner_id'], [$gameMatch->participant1_id, $gameMatch->participant2_id])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El ganador debe ser uno de los participantes del partido'
                ], 400);
            }

            DB::beginTransaction();

            $gameMatch->update([
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

            $this->advanceWinner($gameMatch);

            $this->checkTournamentCompletion($tournament);

            DB::commit();

            $gameMatch->load([
                'participant1.member',
                'participant2.member',
                'winner.member',
                'nextMatch'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Resultado actualizado exitosamente',
                'data' => $gameMatch
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating match result: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el resultado'
            ], 500);
        }
    }

    public function getBracket(Tournament $tournament): JsonResponse
    {
        try {
            $matches = \App\Models\Match::with([
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

    private function generateSingleEliminationMatches(Tournament $tournament, $participants)
    {
        $participantCount = $participants->count();
        $rounds = ceil(log($participantCount, 2));
        $totalSlots = pow(2, $rounds);
        
        // Shuffle participants for random seeding (or use seed if available)
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
                    
                    $gameMatch = \App\Models\Match::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNum,
                        'participant1_id' => $participant1?->id,
                        'participant2_id' => $participant2?->id,
                        'status' => ($participant1 && $participant2) ? 'scheduled' : 'bye',
                        'bracket_position' => $matchNum,
                        'is_bye' => !($participant1 && $participant2)
                    ]);

                    // Handle bye matches
                    if (!$participant2 && $participant1) {
                        $gameMatch->update([
                            'winner_id' => $participant1->id,
                            'status' => 'completed',
                            'score' => 'Bye',
                            'completed_at' => now()
                        ]);
                    }
                } else {
                    $gameMatch = \App\Models\Match::create([
                        'tournament_id' => $tournament->id,
                        'round' => $round,
                        'match_number' => $matchNum,
                        'status' => 'scheduled',
                        'bracket_position' => $matchNum
                    ]);
                }

                $roundMatches->push($gameMatch);
            }

            if ($round < $rounds) {
                $roundMatches->chunk(2)->each(function ($matchPair, $index) use ($round, $tournament) {
                    $nextMatch = \App\Models\Match::where('tournament_id', $tournament->id)
                        ->where('round', $round + 1)
                        ->where('match_number', $index + 1)
                        ->first();

                    if ($nextMatch) {
                        $matchPair->each(function ($gameMatch) use ($nextMatch) {
                            $gameMatch->update(['next_match_id' => $nextMatch->id]);
                        });
                    }
                });
            }

            $matches = $matches->merge($roundMatches);
        }

        return $matches;
    }

    private function generateRoundRobinMatches(Tournament $tournament, $participants)
    {
        $matches = collect();
        $participantList = $participants->values();
        $matchNumber = 1;

        for ($i = 0; $i < $participantList->count(); $i++) {
            for ($j = $i + 1; $j < $participantList->count(); $j++) {
                $gameMatch = \App\Models\Match::create([
                    'tournament_id' => $tournament->id,
                    'round' => 1,
                    'match_number' => $matchNumber,
                    'participant1_id' => $participantList[$i]->id,
                    'participant2_id' => $participantList[$j]->id,
                    'status' => 'scheduled',
                    'bracket_position' => $matchNumber
                ]);

                $matches->push($gameMatch);
                $matchNumber++;
            }
        }

        return $matches;
    }

    private function generateDoubleEliminationMatches(Tournament $tournament, $participants)
    {
        return $this->generateSingleEliminationMatches($tournament, $participants);
    }

    private function advanceWinner(\App\Models\Match $gameMatch)
    {
        if (!$gameMatch->next_match_id || !$gameMatch->winner_id) {
            return;
        }

        $nextMatch = \App\Models\Match::find($gameMatch->next_match_id);
        if (!$nextMatch) {
            return;
        }

        $previousMatches = \App\Models\Match::where('next_match_id', $nextMatch->id)->get();
        $matchIndex = $previousMatches->search(function ($m) use ($gameMatch) {
            return $m->id === $gameMatch->id;
        });

        if ($matchIndex === 0) {
            $nextMatch->update(['participant1_id' => $gameMatch->winner_id]);
        } elseif ($matchIndex === 1) {
            $nextMatch->update(['participant2_id' => $gameMatch->winner_id]);
        }
    }

    private function checkTournamentCompletion(Tournament $tournament)
    {
        $totalMatches = \App\Models\Match::where('tournament_id', $tournament->id)->count();
        $completedMatches = \App\Models\Match::where('tournament_id', $tournament->id)
            ->where('status', 'completed')
            ->count();

        if ($totalMatches > 0 && $totalMatches === $completedMatches) {
            $tournament->update(['status' => 'completed']);
        }

        $tournament->update(['matches_played' => $completedMatches]);
    }

    private function canManageTournament($user, Tournament $tournament): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        if ($user->role === 'club' && $tournament->club_id) {
            $userClub = \App\Models\Club::where('user_id', $user->id)->first();
            return $userClub && $userClub->id === $tournament->club_id;
        }

        if ($user->role === 'liga' && $tournament->league_id) {
            $userLeague = \App\Models\League::where('admin_id', $user->id)->first();
            return $userLeague && $userLeague->id === $tournament->league_id;
        }

        return false;
    }
}