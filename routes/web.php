<?php

use App\Http\Controllers\SportsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('sports', SportsController::class);

// Web team pages (index + show)
use App\Http\Controllers\TeamController as WebTeamController;
Route::get('/teams', [WebTeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team:slug}', [WebTeamController::class, 'show'])->name('teams.show');

// Web player pages (index + show)
use App\Http\Controllers\PlayerController as WebPlayerController;
Route::get('/players', [WebPlayerController::class, 'index'])->name('players.index');
Route::get('/players/{player:slug}', [WebPlayerController::class, 'show'])->name('players.show');

require __DIR__.'/settings.php';
