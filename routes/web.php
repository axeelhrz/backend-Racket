<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TournamentParticipantController;
use App\Http\Controllers\MatchController;

Route::get('/', function () {
    return view('welcome');
});

// Add a debug route to check database connection
Route::get('/debug-db', function () {
    $connection = DB::connection();
    return response()->json([
        'connection_name' => $connection->getName(),
        'driver' => $connection->getDriverName(),
        'database_path' => config('database.connections.sqlite.database'),
        'env_db_connection' => env('DB_CONNECTION'),
        'env_database_url' => env('DATABASE_URL', 'not set'),
    ]);
});

// Add CSRF cookie route for SPA
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->middleware('web');

// Add tournament routes without /api prefix for frontend compatibility
// Removed 'cors' middleware alias as CORS is handled by HandleCors middleware in API routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('tournaments/{tournament}')->group(function () {
        Route::get('/participants', [TournamentParticipantController::class, 'index']);
        Route::post('/participants', [TournamentParticipantController::class, 'store']);
        Route::get('/available-members', [TournamentParticipantController::class, 'availableMembers']);
        Route::put('/participants/{participant}', [TournamentParticipantController::class, 'update']);
        Route::delete('/participants/{participant}', [TournamentParticipantController::class, 'destroy']);
        
        Route::get('/matches', [MatchController::class, 'index']);
        Route::get('/bracket', [MatchController::class, 'getBracket']);
        Route::post('/generate-bracket', [MatchController::class, 'generateBracket']);
        Route::put('/matches/{match}/result', [MatchController::class, 'updateResult']);
    });
});