<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $players = Player::with(['team.sport'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $s = $request->string('q');
                $q->where(function ($qq) use ($s) {
                    $qq->where('first_name', 'like', "%{$s}%")
                       ->orWhere('last_name', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('team_id'), fn($q) => $q->where('team_id', $request->integer('team_id')))
            ->when($request->filled('position'), fn($q) => $q->where('position', $request->string('position')))
            ->orderBy('last_name')
            ->paginate(24)
            ->withQueryString();

        $teams = Team::orderBy('name')->get(['id','name']);

        return Inertia::render('players/Index', [
            'players' => $players,
            'teams' => $teams,
        ]);
    }

    public function show(Player $player)
    {
        $player->load(['team.sport', 'team']);
        
        // Check if stats are stale (older than 6 hours) and need refresh
        $needsSync = !$player->stats_synced_at || $player->stats_synced_at->lt(now()->subHours(6));
        
        $isFavorited = auth()->check() 
            ? auth()->user()->favoritePlayers()->where('player_id', $player->id)->exists()
            : false;
        
        return Inertia::render('players/Show', [
            'player' => $player,
            'needsSync' => $needsSync,
            'isFavorited' => $isFavorited,
        ]);
    }
}