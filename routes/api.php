<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SportParameterController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\QuickRegistrationController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\League;

// Health check endpoint - should be first
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok', 
        'timestamp' => now(),
        'app' => config('app.name'),
        'version' => '1.0.0'
    ]);
});

// Database check endpoint with better error handling
Route::get('/db-check', function () {
    try {
        // Test database connection
        $pdo = DB::connection()->getPdo();
        
        // Test a simple query
        $result = DB::select('SELECT 1 AS test');
        
        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
            'driver' => config('database.default'),
            'test_query' => $result[0]->test ?? null,
            'timestamp' => now()
        ]);
    } catch (\PDOException $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'connection_failed',
            'error' => 'Database connection failed',
            'message' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'query_failed',
            'error' => 'Database query failed',
            'message' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});

// Test endpoint for debugging CORS
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now(),
        'cors_test' => 'success',
        'origin' => request()->header('Origin'),
        'user_agent' => request()->header('User-Agent')
    ]);
});

// Test CORS with POST
Route::post('/test-cors', function (Request $request) {
    return response()->json([
        'message' => 'CORS POST test successful',
        'data' => $request->all(),
        'headers' => [
            'origin' => $request->header('Origin'),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept')
        ],
        'timestamp' => now()
    ]);
});

// Test registration endpoint without CSRF for debugging
Route::post('/test-register', function (Request $request) {
    try {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6'
        ]);

        return response()->json([
            'message' => 'Test registration endpoint working',
            'data' => $data,
            'timestamp' => now()
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Server error',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Admin endpoint to delete specific league (temporary for cleanup)
Route::delete('/admin/delete-league/{name}', function ($name) {
    try {
        // Find the league
        $league = League::where('name', $name)->first();
        
        if (!$league) {
            return response()->json([
                'success' => false,
                'message' => "League '{$name}' not found.",
                'timestamp' => now()
            ], 404);
        }
        
        // Check if league has clubs
        $clubsCount = $league->clubs()->count();
        if ($clubsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete league '{$name}' because it has {$clubsCount} associated clubs.",
                'timestamp' => now()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Get the admin user
            $adminUser = $league->user;
            
            // Delete the league
            $league->delete();
            
            // Delete the admin user if it exists and is only associated with this league
            if ($adminUser && $adminUser->role === 'liga') {
                $adminUser->delete();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "League '{$name}' deleted successfully.",
                'deleted_admin_user' => $adminUser ? $adminUser->name : null,
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting league: ' . $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});

// Quick Registration routes (no authentication required)
Route::prefix('registro-rapido')->group(function () {
    Route::post('/', [QuickRegistrationController::class, 'store']);
    Route::post('/check-email', [QuickRegistrationController::class, 'checkEmail']);
    Route::post('/waiting-room-status', [QuickRegistrationController::class, 'getWaitingRoomStatus']);
});

// Authentication routes (no middleware)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    // Registration helper endpoints
    Route::get('/leagues', [AuthController::class, 'getAvailableLeagues']);
    Route::get('/clubs', [AuthController::class, 'getAvailableClubs']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Quick Registration management (admin only)
    Route::prefix('admin/registro-rapido')->group(function () {
        Route::get('/', [QuickRegistrationController::class, 'index']);
        Route::get('/statistics', [QuickRegistrationController::class, 'getStatistics']);
        Route::get('/{quickRegistration}', [QuickRegistrationController::class, 'show']);
        Route::patch('/{quickRegistration}/status', [QuickRegistrationController::class, 'updateStatus']);
        Route::delete('/{quickRegistration}', [QuickRegistrationController::class, 'destroy']);
    });
    
    // Leagues
    Route::apiResource('leagues', LeagueController::class);
    
    // Clubs - Extended functionality
    Route::apiResource('clubs', ClubController::class);
    
    // Club-specific routes (must be before apiResource to avoid route conflicts)
    Route::prefix('clubs')->group(function () {
        // Club management
        Route::post('/{club}/add-to-league', [ClubController::class, 'addToLeague']);
        Route::post('/{club}/remove-from-league', [ClubController::class, 'removeFromLeague']);
        
        // Club statistics and analytics
        Route::post('/{club}/update-statistics', [ClubController::class, 'updateStatistics']);
        Route::get('/{club}/statistics', [ClubController::class, 'getStatistics']);
        Route::get('/{club}/ranking-history', [ClubController::class, 'getRankingHistory']);
        Route::post('/{club}/ranking-history', [ClubController::class, 'addRankingHistory']);
        
        // Club logo management
        Route::post('/{club}/upload-logo', [ClubController::class, 'uploadLogo']);
        
        // Tournament permissions
        Route::get('/tournament-creators', [ClubController::class, 'getTournamentCreators']);
        Route::post('/{club}/toggle-tournament-permission', [ClubController::class, 'toggleTournamentPermission']);
    });
    
    // Members equipment data (must be before apiResource to avoid route conflicts)
    Route::get('/members/equipment-data', [MemberController::class, 'getEquipmentData']);
    Route::get('/members/statistics', [MemberController::class, 'getStatistics']);
    Route::post('/members/{member}/upload-photo', [MemberController::class, 'uploadPhoto']);
    Route::delete('/members/{member}/photo', [MemberController::class, 'deletePhoto']);
    
    // Members
    Route::apiResource('members', MemberController::class);
    
    // Sports
    Route::apiResource('sports', SportController::class);
    
    // Sport Parameters
    Route::prefix('sports/{sport}')->group(function () {
        Route::get('/parameters', [SportParameterController::class, 'index']);
        Route::post('/parameters', [SportParameterController::class, 'store']);
        Route::put('/parameters/{parameter}', [SportParameterController::class, 'update']);
        Route::delete('/parameters/{parameter}', [SportParameterController::class, 'destroy']);
    });
    
    // Tournaments
    Route::apiResource('tournaments', TournamentController::class);
    
    // Tournament-specific routes
    Route::prefix('tournaments')->group(function () {
        Route::get('/league/{league}', [TournamentController::class, 'getByLeague']);
        Route::get('/league/{league}/stats', [TournamentController::class, 'getLeagueStats']);
    });
    
    // Invitations
    Route::apiResource('invitations', InvitationController::class)->except(['update', 'destroy']);
    
    // Invitation-specific routes
    Route::prefix('invitations')->group(function () {
        Route::post('/{invitation}/accept', [InvitationController::class, 'accept']);
        Route::post('/{invitation}/reject', [InvitationController::class, 'reject']);
        Route::post('/{invitation}/cancel', [InvitationController::class, 'cancel']);
        Route::get('/available-clubs', [InvitationController::class, 'getAvailableClubs']);
        Route::get('/available-leagues', [InvitationController::class, 'getAvailableLeagues']);
        Route::get('/available-entities', [InvitationController::class, 'getAvailableEntities']);
    });
});

// ===== DIAGNOSTIC ROUTES =====

// Echo endpoint for debugging requests
Route::post('/echo-registro', function (Request $req) {
    return response()->json([
        'success' => true,
        'message' => 'Echo endpoint working',
        'received_data' => $req->all(),
        'method' => $req->method(),
        'headers' => [
            'content_type' => $req->header('Content-Type'),
            'accept' => $req->header('Accept'),
            'origin' => $req->header('Origin'),
            'user_agent' => $req->header('User-Agent')
        ],
        'timestamp' => now()
    ]);
});

// Simplified registration endpoint for testing
Route::post('/registro-rapido2', function (Request $req) {
    try {
        // Validate input
        $data = $req->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:6',
        ]);

        // Check if using SQLite or MySQL
        $driver = config('database.default');
        
        if ($driver === 'sqlite') {
            // SQLite version
            DB::statement("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(120) NOT NULL,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        } else {
            // MySQL version
            DB::statement("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(120) NOT NULL,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        // Try to insert user
        try {
            DB::insert(
                'INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [
                    $data['name'], 
                    $data['email'], 
                    Hash::make($data['password']),
                    now(),
                    now()
                ]
            );
        } catch (\Throwable $e) {
            // Check for duplicate email error
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || 
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), '23000')) {
                return response()->json([
                    'success' => false,
                    'error' => 'EMAIL_ALREADY_EXISTS',
                    'message' => 'Este email ya está registrado'
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'name' => $data['name'],
                'email' => $data['email']
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'VALIDATION_ERROR',
            'message' => 'Datos de entrada inválidos',
            'errors' => $e->errors()
        ], 422);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => 'SERVER_ERROR',
            'message' => 'Error interno del servidor',
            'details' => $e->getMessage(),
            'database_driver' => config('database.default')
        ], 500);
    }
});