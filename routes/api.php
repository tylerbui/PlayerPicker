<?php

use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\PlayerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API v1
Route::prefix('v1')->as('api.v1.')->group(function () {
    // Health
    Route::get('health', fn () => ['ok' => true, 'ts' => now()->toISOString()])->name('health');

    // Teams
    Route::apiResource('teams', TeamController::class)->only(['index', 'show']);

    // Players
    Route::get('players/search', [PlayerController::class, 'search'])->name('players.search');
Route::apiResource('players', PlayerController::class)->only(['index', 'show']);

    // Live player line (near-live from ESPN)
    Route::get('players/{player:slug}/live', [PlayerController::class, 'live'])->name('players.live');
    // Recent games (last 5) with per-game lines
    Route::get('players/{player:slug}/recent', [PlayerController::class, 'recent'])->name('players.recent');
    // Averages (from stored season blobs)
    Route::get('players/{player:slug}/averages', [PlayerController::class, 'averages'])->name('players.averages');
});
