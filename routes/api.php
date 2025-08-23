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


Route::get('/db-check', function () {
  try {
    $r = DB::select('SELECT 1 AS ok');
    return response()->json(['db' => 'ok', 'result' => $r[0]->ok ?? null]);
  } catch (\Throwable $e) {
    return response()->json(['db' => 'fail', 'error' => $e->getMessage()], 500);
  }
});

// Test endpoint for debugging
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now(),
        'cors_test' => 'success'
    ]);
});

// Test registration endpoint without CSRF for debugging
Route::post('/test-register', function (Request $request) {
    return response()->json([
        'message' => 'Test registration endpoint working',
        'data' => $request->all(),
        'timestamp' => now()
    ]);
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

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// ===== RUTAS DE DIAGNÓSTICO =====

// 1) eco para confirmar que la request llega y vemos el body
Route::post('/echo-registro', function (Request $req) {
    return response()->json([
        'ok' => true,
        'received' => $req->all(),
        'method' => $req->method(),
    ]);
});

// 2) versión mínima y robusta (NO usa tus modelos). Crea tabla si falta, inserta y maneja errores.
Route::post('/registro-rapido2', function (Request $req) {
    try {
        $data = $req->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:6',
        ]);

        DB::statement("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        try {
            DB::insert(
                'INSERT INTO users (name, email, password) VALUES (?, ?, ?)',
                [$data['name'], $data['email'], Hash::make($data['password'])]
            );
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '23000')) {
                return response()->json(['ok'=>false,'error'=>'EMAIL_TAKEN'], 409);
            }
            throw $e;
        }

        return response()->json(['ok'=>true], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['ok'=>false,'error'=>'VALIDATION','messages'=>$e->errors()], 422);
    } catch (\Throwable $e) {
        return response()->json(['ok'=>false,'error'=>'SERVER','message'=>$e->getMessage()], 500);
    }
});