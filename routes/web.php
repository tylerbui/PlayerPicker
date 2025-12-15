<?php

use App\Http\Controllers\SportsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $sports = \App\Models\Sport::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'slug', 'icon', 'description']);
    
    return Inertia::render('SportSelect', [
        'sports' => $sports
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'selectedLevel' => request()->query('level', 'all'),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Show sport detail with leagues
Route::get('/sports/{sport:slug}', function (\App\Models\Sport $sport) {
    $leagues = $sport->leagues()
        ->where('is_active', true)
        ->with('sport')
        ->orderBy('name')
        ->get();
    
    return Inertia::render('SportDetail', [
        'sport' => $sport,
        'leagues' => $leagues
    ]);
})->name('sports.show');

Route::resource('sports', SportsController::class)->except(['show']);

// Web team pages (index + show)
use App\Http\Controllers\TeamController as WebTeamController;
Route::get('/teams', [WebTeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team:slug}', [WebTeamController::class, 'show'])->name('teams.show');

// Web player pages (index + show)
use App\Http\Controllers\PlayerController as WebPlayerController;
Route::get('/players', [WebPlayerController::class, 'index'])->name('players.index');
Route::get('/players/{player:slug}', [WebPlayerController::class, 'show'])->name('players.show');

require __DIR__.'/settings.php';
