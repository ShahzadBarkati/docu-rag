<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    try {
        // Check database connection
        $db = DB::connection()->getPdo();

        // Check if we can query a simple table
        DB::select('SELECT 1');

        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => 'connected',
                'application' => 'running',
            ],
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toISOString(),
            'error' => $e->getMessage(),
        ], 503);
    }
});
