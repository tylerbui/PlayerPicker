<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    // GET /api/v1/players
    public function index(Request $request)
    {
        $request->validate([
            'team_id' => 'sometimes|integer|exists:teams,id',
            'sport_id' => 'sometimes|integer|exists:sports,id',
            'position' => 'sometimes|string',
            'q' => 'sometimes|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'order_by' => 'sometimes|in:last_name,number,created_at',
            'order_dir' => 'sometimes|in:asc,desc',
        ]);

        $query = Player::query()->with(['team.sport']);

        $query->when($request->filled('team_id'), fn($q) => $q->where('team_id', $request->integer('team_id')));
        $query->when($request->filled('sport_id'), function ($q) use ($request) {
            $q->whereHas('team', fn($qq) => $qq->where('sport_id', $request->integer('sport_id')));
        });
        $query->when($request->filled('position'), fn($q) => $q->where('position', $request->string('position')));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $q->where(function ($qq) use ($request) {
                $s = $request->string('q');
                $qq->where('first_name', 'LIKE', "%{$s}%")
                   ->orWhere('last_name', 'LIKE', "%{$s}%");
            });
        });

        $orderBy = $request->get('order_by', 'last_name');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = (int) $request->get('per_page', 24);
        $players = $query->paginate($perPage)->withQueryString();

        return PlayerResource::collection($players);
    }

    // GET /api/v1/players/{player}
    public function show(Player $player)
    {
        $player->load(['team.sport']);
        return new PlayerResource($player);
    }

    // GET /api/v1/players/search?q=
    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string', 'per_page' => 'sometimes|integer|min:1|max:50']);

        $players = Player::with(['team.sport'])
            ->where(function ($q) use ($request) {
                $s = $request->string('q');
                $q->where('first_name', 'LIKE', "%{$s}%")
                  ->orWhere('last_name', 'LIKE', "%{$s}%");
            })
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return PlayerResource::collection($players);
    }
}
