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
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\TournamentParticipantController;
use App\Http\Controllers\MatchController;
use Illuminate\Support\Facades\DB;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
});

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

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    
    Route::get('/available-leagues', [AuthController::class, 'getAvailableLeagues']);
    Route::get('/available-clubs', [AuthController::class, 'getAvailableClubs']);
    Route::get('/leagues', [AuthController::class, 'getAvailableLeagues']);
    Route::get('/clubs', [AuthController::class, 'getAvailableClubs']);
});

Route::prefix('equipment')->group(function () {
    Route::get('/data', [EquipmentController::class, 'getEquipmentData']);
    Route::get('/rubber-brands', [EquipmentController::class, 'getRubberBrands']);
    Route::get('/rubber-models', [EquipmentController::class, 'getRubberModels']);
    Route::get('/racket-brands', [EquipmentController::class, 'getRacketBrands']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/rubber-brands', [EquipmentController::class, 'addRubberBrand']);
        Route::post('/rubber-models', [EquipmentController::class, 'addRubberModel']);
        Route::put('/rubber-models/{id}', [EquipmentController::class, 'updateRubberModel']);
        Route::delete('/rubber-models/{id}', [EquipmentController::class, 'deleteRubberModel']);
        Route::post('/racket-models', [EquipmentController::class, 'addRacketModel']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('leagues', LeagueController::class);
    
    Route::apiResource('clubs', ClubController::class);
    
    Route::apiResource('members', MemberController::class);
    Route::get('members/equipment/data', [MemberController::class, 'getEquipmentData']);
    Route::get('members/available-clubs', [MemberController::class, 'getAvailableClubs']);
    
    Route::apiResource('sports', SportController::class);
    Route::get('sports/{sport}/parameters', [SportParameterController::class, 'index']);
    Route::post('sports/{sport}/parameters', [SportParameterController::class, 'store']);
    Route::put('sports/{sport}/parameters/{parameter}', [SportParameterController::class, 'update']);
    Route::delete('sports/{sport}/parameters/{parameter}', [SportParameterController::class, 'destroy']);
    
    Route::apiResource('tournaments', TournamentController::class);
    
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
    
    Route::apiResource('invitations', InvitationController::class);
});

Route::prefix('registro-rapido')->group(function () {
    Route::post('/', [QuickRegistrationController::class, 'store']);
    Route::get('/', [QuickRegistrationController::class, 'index']);
    Route::get('/{code}', [QuickRegistrationController::class, 'show']);
    
    Route::post('/add-custom-field', [QuickRegistrationController::class, 'addCustomField']);
    Route::get('/field-options/{fieldType}', [QuickRegistrationController::class, 'getFieldOptions']);
    Route::post('/validate-custom-field', [QuickRegistrationController::class, 'validateCustomField']);
    Route::get('/field-suggestions/{field_type}', [QuickRegistrationController::class, 'getFieldSuggestions']);
    
    Route::get('/existing-clubs', [QuickRegistrationController::class, 'getExistingClubs']);
    Route::get('/existing-leagues', [QuickRegistrationController::class, 'getExistingLeagues']);
    
    Route::post('/waiting-room-status', [QuickRegistrationController::class, 'getWaitingRoomStatus']);
});

Route::post('/registro-rapido', [QuickRegistrationController::class, 'store']);
Route::get('/registro-rapido', [QuickRegistrationController::class, 'index']);
Route::get('/registro-rapido/{code}', [QuickRegistrationController::class, 'show']);

Route::post('/add-custom-field', [QuickRegistrationController::class, 'addCustomField']);
Route::get('/field-options/{fieldType}', [QuickRegistrationController::class, 'getFieldOptions']);
Route::post('/validate-custom-field', [QuickRegistrationController::class, 'validateCustomField']);
Route::get('/field-suggestions/{field_type}', [QuickRegistrationController::class, 'getFieldSuggestions']);

Route::get('/existing-clubs', [QuickRegistrationController::class, 'getExistingClubs']);
Route::get('/existing-leagues', [QuickRegistrationController::class, 'getExistingLeagues']);

Route::post('/registro-rapido/waiting-room-status', [QuickRegistrationController::class, 'getWaitingRoomStatus']);