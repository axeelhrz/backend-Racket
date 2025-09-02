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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
});

// Database test route
Route::get('/test-db', function () {
    try {
        $result = DB::select('SELECT 1 as test');
        return response()->json([
            'message' => 'Database connection successful!',
            'result' => $result,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Database connection failed!',
            'error' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Leagues
    Route::apiResource('leagues', LeagueController::class);
    
    // Clubs
    Route::apiResource('clubs', ClubController::class);
    
    // Members
    Route::apiResource('members', MemberController::class);
    
    // Sports
    Route::apiResource('sports', SportController::class);
    Route::get('sports/{sport}/parameters', [SportParameterController::class, 'index']);
    Route::post('sports/{sport}/parameters', [SportParameterController::class, 'store']);
    Route::put('sports/{sport}/parameters/{parameter}', [SportParameterController::class, 'update']);
    Route::delete('sports/{sport}/parameters/{parameter}', [SportParameterController::class, 'destroy']);
    
    // Tournaments
    Route::apiResource('tournaments', TournamentController::class);
    
    // Invitations
    Route::apiResource('invitations', InvitationController::class);
});

// Quick Registration routes (public)
Route::prefix('registro-rapido')->group(function () {
    Route::post('/', [QuickRegistrationController::class, 'store']);
    Route::get('/', [QuickRegistrationController::class, 'index']);
    Route::get('/{code}', [QuickRegistrationController::class, 'show']);
    
    // NUEVAS RUTAS: Campos personalizados
    Route::post('/add-custom-field', [QuickRegistrationController::class, 'addCustomField']);
    Route::get('/field-options/{fieldType}', [QuickRegistrationController::class, 'getFieldOptions']);
    Route::post('/validate-custom-field', [QuickRegistrationController::class, 'validateCustomField']);
    Route::get('/field-suggestions/{field_type}', [QuickRegistrationController::class, 'getFieldSuggestions']);
});

// Legacy routes for backward compatibility
Route::post('/registro-rapido', [QuickRegistrationController::class, 'store']);
Route::get('/registro-rapido', [QuickRegistrationController::class, 'index']);
Route::get('/registro-rapido/{code}', [QuickRegistrationController::class, 'show']);

// NUEVAS RUTAS LEGACY: Campos personalizados
Route::post('/add-custom-field', [QuickRegistrationController::class, 'addCustomField']);
Route::get('/field-options/{fieldType}', [QuickRegistrationController::class, 'getFieldOptions']);
Route::post('/validate-custom-field', [QuickRegistrationController::class, 'validateCustomField']);
Route::get('/field-suggestions/{field_type}', [QuickRegistrationController::class, 'getFieldSuggestions']);