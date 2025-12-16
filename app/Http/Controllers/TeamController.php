<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $teams = Team::with('sport')
            ->withCount('players')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                       ->orWhere('city', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('conference'), function ($q) use ($request) {
                $q->where('extra_data->leagues->standard->conference', $request->conference);
            })
            ->when($request->filled('division'), function ($q) use ($request) {
                $q->where('extra_data->leagues->standard->division', $request->division);
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('teams/Index', [
            'teams' => $teams,
        ]);
    }

    public function show(Team $team)
    {
        $team->load([
            'sport',
            'players' => fn($q) => $q->orderBy('number')->orderBy('last_name'),
        ]);

        $isFavorited = auth()->check() 
            ? auth()->user()->favoriteTeams()->where('team_id', $team->id)->exists()
            : false;

        return Inertia::render('teams/Show', [
            'team' => $team,
            'isFavorited' => $isFavorited,
        ]);
    }
}