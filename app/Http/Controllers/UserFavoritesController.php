<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;

class UserFavoritesController extends Controller
{
    public function toggleFavoriteTeam(Request $request, Team $team)
    {
        $user = $request->user();
        
        if ($user->favoriteTeams()->where('team_id', $team->id)->exists()) {
            $user->favoriteTeams()->detach($team->id);
            $isFavorited = false;
        } else {
            $user->favoriteTeams()->attach($team->id);
            $isFavorited = true;
        }
        
        return back()->with([
            'message' => $isFavorited ? 'Team added to favorites' : 'Team removed from favorites',
            'isFavorited' => $isFavorited,
        ]);
    }
    
    public function toggleFavoritePlayer(Request $request, Player $player)
    {
        $user = $request->user();
        
        if ($user->favoritePlayers()->where('player_id', $player->id)->exists()) {
            $user->favoritePlayers()->detach($player->id);
            $isFavorited = false;
        } else {
            $user->favoritePlayers()->attach($player->id);
            $isFavorited = true;
        }
        
        return back()->with([
            'message' => $isFavorited ? 'Player added to favorites' : 'Player removed from favorites',
            'isFavorited' => $isFavorited,
        ]);
    }
}
