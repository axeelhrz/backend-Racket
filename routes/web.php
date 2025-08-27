<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

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