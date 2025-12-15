<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     * 
     * Optional filters:
     * - sport_id: Filter by sport
     * - search: Search by name or city
     */
    public function index(Request $request)
    {
        $request->validate([
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'search' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'country' => 'sometimes|string',
            'conference' => 'sometimes|string',
            'division' => 'sometimes|string',
            'nba_franchise' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'order_by' => 'sometimes|in:name,city,players_count,created_at',
            'order_dir' => 'sometimes|in:asc,desc',
        ]);

        $query = Team::with('sport')
            ->withCount('players');

        // Filter by sport
        $query->when($request->filled('sport_id'), fn($q) => $q->where('sport_id', $request->integer('sport_id')));

        // Search by name, city, or code
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->string('search');
            $q->where(function ($qq) use ($search) {
                $qq->where('name', 'LIKE', "%{$search}%")
                   ->orWhere('city', 'LIKE', "%{$search}%")
                   ->orWhere('code', 'LIKE', "%{$search}%");
            });
        });

        // Simple geo filters
        $query->when($request->filled('city'), fn($q) => $q->where('city', 'LIKE', "%{$request->city}%"));
        $query->when($request->filled('state'), fn($q) => $q->where('state', 'LIKE', "%{$request->state}%"));
        $query->when($request->filled('country'), fn($q) => $q->where('country', 'LIKE', "%{$request->country}%"));

        // NBA-specific filters from JSON extra_data
        // conference/division stored at extra_data->leagues->standard->conference|division
        $query->when($request->filled('conference'), function ($q) use ($request) {
            $q->where('extra_data->leagues->standard->conference', $request->conference);
        });
        $query->when($request->filled('division'), function ($q) use ($request) {
            $q->where('extra_data->leagues->standard->division', $request->division);
        });
        $query->when($request->boolean('nba_franchise', false), function ($q) {
            $q->where('extra_data->nbaFranchise', true);
        });

        // Ordering
        $orderBy = $request->get('order_by', 'name');
        $orderDir = $request->get('order_dir', 'asc');
        if ($orderBy === 'players_count') {
            $query->orderBy('players_count', $orderDir);
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        // Paginate results
        $perPage = (int) $request->get('per_page', 15);
        $teams = $query->paginate($perPage);

        return TeamResource::collection($teams);
    }

    /**
     * Display the specified team with full roster.
     */
    public function show(Team $team)
    {
        // Load relationships
        $team->load([
            'sport',
            'players' => function($query) {
                $query->orderBy('number')
                      ->orderBy('last_name');
            }
        ]);

        return new TeamResource($team);
    }
}
