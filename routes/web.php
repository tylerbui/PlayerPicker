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
    $user = auth()->user();
    $level = request()->query('level', request()->session()->get('selected_level', 'all'));
    
    // Build query for favorite teams based on level
    $favoriteTeamsQuery = $user->favoriteTeams()
        ->with(['sport', 'league']);
    
    if ($level !== 'all') {
        $favoriteTeamsQuery->whereHas('league', function ($q) use ($level) {
            $q->where('category', $level);
        });
    }
    
    $favoriteTeams = $favoriteTeamsQuery->limit(6)->get();
    
    // Build query for favorite players based on level
    $favoritePlayersQuery = $user->favoritePlayers()
        ->with(['team.sport', 'team.league']);
    
    if ($level !== 'all') {
        $favoritePlayersQuery->whereHas('team.league', function ($q) use ($level) {
            $q->where('category', $level);
        });
    }
    
    $favoritePlayers = $favoritePlayersQuery->limit(6)->get();
    
    return Inertia::render('Dashboard', [
        'selectedLevel' => $level,
        'favoriteTeams' => $favoriteTeams,
        'favoritePlayers' => $favoritePlayers,
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

// League teams page
Route::get('/leagues/{league:slug}/teams', function (\App\Models\League $league) {
    $query = $league->teams()
        ->with('sport')
        ->where('is_active', true);
    
    // For NCAA leagues, prioritize teams with players
    if ($league->api_type === 'ncaa') {
        $teams = $query
            ->withCount('players')
            ->get()
            ->sortBy([
                // First: teams WITH players come first
                fn($a, $b) => ($b->players_count > 0 ? 1 : 0) <=> ($a->players_count > 0 ? 1 : 0),
                // Second: alphabetically by name
                fn($a, $b) => strcasecmp($a->name, $b->name),
            ])
            ->values();
    } else {
        // For non-NCAA leagues, just sort alphabetically
        $teams = $query->orderBy('name')->get();
    }
    
    return Inertia::render('teams/Index', [
        'teams' => $teams,
        'league' => $league,
    ]);
})->name('leagues.teams');

// Web team pages (index + show)
use App\Http\Controllers\TeamController as WebTeamController;
Route::get('/teams', [WebTeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team:slug}', [WebTeamController::class, 'show'])->name('teams.show');

// Web player pages (index + show)
use App\Http\Controllers\PlayerController as WebPlayerController;
Route::get('/players', [WebPlayerController::class, 'index'])->name('players.index');
Route::get('/players/{player:slug}', [WebPlayerController::class, 'show'])->name('players.show');

// Favorites routes
use App\Http\Controllers\UserFavoritesController;
Route::middleware(['auth'])->group(function () {
    Route::post('/favorites/teams/{team:slug}', [UserFavoritesController::class, 'toggleFavoriteTeam'])->name('favorites.teams.toggle');
    Route::post('/favorites/players/{player:slug}', [UserFavoritesController::class, 'toggleFavoritePlayer'])->name('favorites.players.toggle');
});

require __DIR__.'/settings.php';
