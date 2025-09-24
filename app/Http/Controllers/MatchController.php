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

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Matches endpoint working'
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

            Log::info('User authorized');

            // Check database connection
            try {
                $dbTest = DB::select('SELECT 1 as test');
                Log::info('Database connection OK');
            } catch (\Exception $e) {
                Log::error('Database connection failed: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error de conexión a la base de datos'
                ], 500);
            }

            // Check what tables exist
            try {
                $tables = DB::select('SHOW TABLES');
                $tableNames = array_map(function($table) {
                    return array_values((array)$table)[0];
                }, $tables);
                
                Log::info('Available tables: ' . implode(', ', $tableNames));
                
                $hasMatches = in_array('tournament_matches', $tableNames) || in_array('matches', $tableNames);
                $hasParticipants = in_array('tournament_participants', $tableNames);
                
                Log::info('Has matches table: ' . ($hasMatches ? 'YES' : 'NO'));
                Log::info('Has participants table: ' . ($hasParticipants ? 'YES' : 'NO'));
                
                if (!$hasMatches) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tabla de partidos no encontrada. Tablas disponibles: ' . implode(', ', $tableNames)
                    ], 500);
                }
                
                if (!$hasParticipants) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tabla de participantes no encontrada. Tablas disponibles: ' . implode(', ', $tableNames)
                    ], 500);
                }
                
            } catch (\Exception $e) {
                Log::error('Error checking tables: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error verificando tablas: ' . $e->getMessage()
                ], 500);
            }

            // Try to get participants count
            try {
                $participantCount = DB::table('tournament_participants')
                    ->where('tournament_id', $tournament->id)
                    ->count();
                    
                Log::info('Participant count: ' . $participantCount);
                
                if ($participantCount < 2) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Se necesitan al menos 2 participantes para generar el bracket. Encontrados: ' . $participantCount
                    ], 400);
                }
                
            } catch (\Exception $e) {
                Log::error('Error counting participants: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error contando participantes: ' . $e->getMessage()
                ], 500);
            }

            // Check if matches already exist
            try {
                $existingMatches = DB::table('tournament_matches')
                    ->where('tournament_id', $tournament->id)
                    ->count();
                    
                Log::info('Existing matches: ' . $existingMatches);
                
                if ($existingMatches > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El bracket ya ha sido generado para este torneo (' . $existingMatches . ' partidos existentes)'
                    ], 400);
                }
                
            } catch (\Exception $e) {
                Log::error('Error checking existing matches: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error verificando partidos existentes: ' . $e->getMessage()
                ], 500);
            }

            // Get table structure for tournament_matches
            try {
                $columns = DB::select('SHOW COLUMNS FROM tournament_matches');
                $columnNames = array_map(function($col) {
                    return $col->Field;
                }, $columns);
                
                Log::info('tournament_matches columns: ' . implode(', ', $columnNames));
                
                $requiredColumns = ['tournament_id', 'round', 'match_number'];
                $missingColumns = array_diff($requiredColumns, $columnNames);
                
                if (!empty($missingColumns)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Columnas faltantes en tournament_matches: ' . implode(', ', $missingColumns)
                    ], 500);
                }
                
            } catch (\Exception $e) {
                Log::error('Error checking table structure: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error verificando estructura de tabla: ' . $e->getMessage()
                ], 500);
            }

            // Try to create a simple test match
            try {
                $testMatchId = DB::table('tournament_matches')->insertGetId([
                    'tournament_id' => $tournament->id,
                    'round' => 1,
                    'match_number' => 1,
                    'status' => 'test',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('Test match created with ID: ' . $testMatchId);
                
                // Delete the test match
                DB::table('tournament_matches')->where('id', $testMatchId)->delete();
                Log::info('Test match deleted');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Bracket generation test successful. Ready to generate real bracket.',
                    'debug_info' => [
                        'tournament_id' => $tournament->id,
                        'participant_count' => $participantCount,
                        'test_match_id' => $testMatchId
                    ]
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error creating test match: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Error creando partido de prueba: ' . $e->getMessage()
                ], 500);
            }

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
     * Update match result
     */
    public function updateResult(Request $request, Tournament $tournament, TournamentMatch $match): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Update result not implemented yet'
        ], 501);
    }

    /**
     * Get tournament bracket
     */
    public function getBracket(Tournament $tournament): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'tournament' => $tournament,
                    'bracket' => [],
                    'total_rounds' => 0,
                    'completed_matches' => 0,
                    'total_matches' => 0
                ],
                'message' => 'Bracket endpoint working'
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tournament bracket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el bracket'
            ], 500);
        }
    }
}