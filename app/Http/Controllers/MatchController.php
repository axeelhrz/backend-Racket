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

            // Check if matches table exists
            try {
                $tableExists = DB::select("SHOW TABLES LIKE 'tournament_matches'");
                if (empty($tableExists)) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No matches table found'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error checking matches table: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Database error'
                ]);
            }

            // Simple query to get matches
            try {
                $matches = DB::table('tournament_matches')
                    ->where('tournament_id', $tournament->id)
                    ->orderBy('round')
                    ->orderBy('match_number')
                    ->get();

                return response()->json([
                    'success' => true,
                    'data' => $matches
                ]);
            } catch (\Exception $e) {
                Log::error('Error fetching matches: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Query error'
                ]);
            }

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
            Log::info('Generating bracket for tournament: ' . $tournament->id);

            // Verificar permisos
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            if (!$this->canManageTournament($user, $tournament)) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            // Check if matches table exists
            try {
                $tableExists = DB::select("SHOW TABLES LIKE 'tournament_matches'");
                if (empty($tableExists)) {
                    Log::warning('tournament_matches table does not exist');
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede generar el bracket: tabla de partidos no encontrada'
                    ], 500);
                }
            } catch (\Exception $e) {
                Log::error('Error checking matches table: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error de base de datos'
                ], 500);
            }

            // Verificar si ya existe un bracket
            try {
                $existingMatches = DB::table('tournament_matches')
                    ->where('tournament_id', $tournament->id)
                    ->count();

                if ($existingMatches > 0) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'El bracket ya ha sido generado para este torneo'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Error checking existing matches: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error verificando partidos existentes'
                ], 500);
            }

            // Obtener participantes usando query builder
            try {
                $participants = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->where('status', 'registered')
                    ->get();

                Log::info('Found participants: ' . $participants->count());

                if ($participants->count() < 2) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Se necesitan al menos 2 participantes para generar el bracket'
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error('Error fetching participants: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error obteniendo participantes'
                ], 500);
            }

            // Generar bracket según el formato del torneo
            try {
                $this->generateMatchesForFormat($tournament, $participants);

                return response()->json([
                    'success' => true,
                    'message' => 'Bracket generado exitosamente',
                    'participants_count' => $participants->count()
                ]);
            } catch (\Exception $e) {
                Log::error('Error generating matches: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error generando partidos: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error generating bracket: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
                'winner_id' => 'required|integer',
                'score' => 'nullable|string|max:255',
                'score_p1' => 'nullable|integer|min:0',
                'score_p2' => 'nullable|integer|min:0',
                'duration_minutes' => 'nullable|integer|min:1',
                'notes' => 'nullable|string|max:1000',
            ]);

            // Simple update using query builder
            DB::table('tournament_matches')
                ->where('id', $match->id)
                ->update([
                    'winner_id' => $validatedData['winner_id'],
                    'status' => 'completed',
                    'completed_at' => now(),
                    'score' => $validatedData['score'] ?? null,
                    'participant1_score' => $validatedData['score_p1'] ?? null,
                    'participant2_score' => $validatedData['score_p2'] ?? null,
                    'duration_minutes' => $validatedData['duration_minutes'] ?? null,
                    'notes' => $validatedData['notes'] ?? null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Resultado actualizado exitosamente'
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
            // Check if matches table exists
            try {
                $tableExists = DB::select("SHOW TABLES LIKE 'tournament_matches'");
                if (empty($tableExists)) {
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'tournament' => $tournament,
                            'bracket' => [],
                            'total_rounds' => 0,
                            'completed_matches' => 0,
                            'total_matches' => 0
                        ],
                        'message' => 'No matches table found'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error checking matches table: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tournament' => $tournament,
                        'bracket' => [],
                        'total_rounds' => 0,
                        'completed_matches' => 0,
                        'total_matches' => 0
                    ],
                    'message' => 'Database error'
                ]);
            }

            // Simple query to get matches
            try {
                $matches = DB::table('tournament_matches')
                    ->where('tournament_id', $tournament->id)
                    ->orderBy('round')
                    ->orderBy('bracket_position')
                    ->get();

                // Group matches by round
                $bracket = [];
                foreach ($matches as $match) {
                    $round = $match->round ?? 1;
                    if (!isset($bracket[$round])) {
                        $bracket[$round] = [];
                    }
                    $bracket[$round][] = $match;
                }

                $totalMatches = $matches->count();
                $completedMatches = collect($matches)->where('status', 'completed')->count();
                $maxRound = collect($matches)->max('round') ?? 0;

                return response()->json([
                    'success' => true,
                    'data' => [
                        'tournament' => $tournament,
                        'bracket' => $bracket,
                        'total_rounds' => $maxRound,
                        'completed_matches' => $completedMatches,
                        'total_matches' => $totalMatches
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('Error fetching bracket: ' . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tournament' => $tournament,
                        'bracket' => [],
                        'total_rounds' => 0,
                        'completed_matches' => 0,
                        'total_matches' => 0
                    ],
                    'message' => 'Query error'
                ]);
            }

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

        // Shuffle participants for random seeding
        $shuffledParticipants = collect($participants)->shuffle();
        
        // Generate first round matches
        $firstRoundMatches = $bracketSize / 2;
        $participantIndex = 0;
        
        for ($matchNum = 1; $matchNum <= $firstRoundMatches; $matchNum++) {
            $participant1 = $participantIndex < $participantCount ? $shuffledParticipants[$participantIndex] : null;
            $participantIndex++;
            $participant2 = $participantIndex < $participantCount ? $shuffledParticipants[$participantIndex] : null;
            $participantIndex++;
            
            $matchData = [
                'tournament_id' => $tournament->id,
                'round' => 1,
                'match_number' => $matchNum,
                'participant1_id' => $participant1 ? $participant1->id : null,
                'participant2_id' => $participant2 ? $participant2->id : null,
                'status' => 'scheduled',
                'bracket_position' => $matchNum,
                'is_bye' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Handle bye matches
            if ($participant1 && !$participant2) {
                $matchData['status'] = 'completed';
                $matchData['is_bye'] = true;
                $matchData['winner_id'] = $participant1->id;
                $matchData['score'] = 'Bye';
                $matchData['completed_at'] = now();
            }
            
            DB::table('tournament_matches')->insert($matchData);
        }
        
        // Generate subsequent rounds (empty matches to be filled as tournament progresses)
        for ($round = 2; $round <= $rounds; $round++) {
            $matchesInRound = $bracketSize / pow(2, $round);
            
            for ($matchNum = 1; $matchNum <= $matchesInRound; $matchNum++) {
                DB::table('tournament_matches')->insert([
                    'tournament_id' => $tournament->id,
                    'round' => $round,
                    'match_number' => $matchNum,
                    'participant1_id' => null,
                    'participant2_id' => null,
                    'status' => 'scheduled',
                    'bracket_position' => $matchNum,
                    'is_bye' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        Log::info('Bracket generated successfully');
    }

    /**
     * Generate round robin matches
     */
    private function generateRoundRobinMatches(Tournament $tournament, $participants)
    {
        $participantList = collect($participants)->values();
        $matchNumber = 1;

        // Create matches for every participant combination
        for ($i = 0; $i < $participantList->count(); $i++) {
            for ($j = $i + 1; $j < $participantList->count(); $j++) {
                DB::table('tournament_matches')->insert([
                    'tournament_id' => $tournament->id,
                    'round' => 1,
                    'match_number' => $matchNumber,
                    'participant1_id' => $participantList[$i]->id,
                    'participant2_id' => $participantList[$j]->id,
                    'status' => 'scheduled',
                    'bracket_position' => $matchNumber,
                    'is_bye' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $matchNumber++;
            }
        }

        Log::info('Round robin matches generated successfully');
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

        // Club user can manage their club's tournaments
        if ($user->role === 'club' && $tournament->club_id) {
            try {
                $userClub = DB::table('clubs')->where('user_id', $user->id)->first();
                return $userClub && $userClub->id === $tournament->club_id;
            } catch (\Exception $e) {
                Log::error('Error checking user club: ' . $e->getMessage());
                return false;
            }
        }

        // League admin can manage their league's tournaments
        if ($user->role === 'liga' && $tournament->league_id) {
            try {
                $userLeague = DB::table('leagues')->where('admin_id', $user->id)->first();
                return $userLeague && $userLeague->id === $tournament->league_id;
            } catch (\Exception $e) {
                Log::error('Error checking user league: ' . $e->getMessage());
                return false;
            }
        }

        return false;
    }
}