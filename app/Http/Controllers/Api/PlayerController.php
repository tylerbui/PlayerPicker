<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function live(Player $player, \App\Services\EspnService $espn)
    {
        $player->load('team');
        $teamAbbr = $player->team?->code;
        if (!$teamAbbr) {
            return response()->json(['ok' => false, 'message' => 'Team abbreviation missing for player team'], 422);
        }

        // Try by team abbr, then fall back to scanning by player name across today's games
        $eventId = $espn->findTodayEventForTeamAbbr($teamAbbr, true)
            ?? $espn->findTodayEventForTeamAbbr($teamAbbr, false);

        $fullName = trim($player->first_name . ' ' . $player->last_name);
        $foundByName = null;
        if (!$eventId) {
            $foundByName = $espn->findTodayEventForPlayerName($fullName, false);
            if ($foundByName) {
                $eventId = $foundByName['eventId'];
                $teamAbbr = $foundByName['teamAbbr'];
            }
        }

        if (!$eventId) {
            return response()->json(['ok' => false, 'live' => false, 'message' => 'No game found for player today'], 404);
        }

        $summary = $espn->getGameSummary($eventId);
        if (!$summary) {
            return response()->json(['ok' => false, 'message' => 'Failed to load ESPN game summary'], 502);
        }

        $fullName = trim($player->first_name . ' ' . $player->last_name);
        $line = $espn->extractPlayerLiveLine($summary, $fullName, $teamAbbr);
        if (!$line && !$foundByName) {
            // One more attempt without relying on team
            $any = $espn->extractPlayerFromSummaryAnyTeam($summary, $fullName);
            if ($any) { $line = $any['line']; $teamAbbr = $any['teamAbbr'] ?? $teamAbbr; }
        }

        $state = data_get($summary, 'header.competitions.0.status.type.state');
        $clock = data_get($summary, 'header.competitions.0.status.type.detail');

        return response()->json([
            'ok' => true,
            'live' => $state === 'in',
            'state' => $state,
            'clock' => $clock,
            'eventId' => $eventId,
            'player' => [
                'id' => $player->id,
                'name' => $fullName,
                'teamAbbr' => $teamAbbr,
            ],
            'line' => $line,
            'source' => 'espn',
        ]);
}

    public function recent(Player $player, \App\Services\EspnService $espn)
    {
        $player->load('team');
        $teamAbbr = $player->team?->code;
        if (!$teamAbbr) {
            return response()->json(['ok' => false, 'message' => 'Team abbreviation missing for player team'], 422);
        }
        // Prefer robust name-based search to avoid stale team mappings
        $fullName = trim($player->first_name . ' ' . $player->last_name);
        $games = $espn->findRecentGamesByPlayerName($fullName, 5, 30);
        return response()->json(['ok' => true, 'games' => $games]);
    }

    public function averages(Player $player)
    {
        // Derive simple per-game averages from stored season blobs if present
        $curr = $player->current_season_stats ?? [];
        $prev = $player->previous_season_stats ?? [];

        $avgFrom = function ($blob) {
            if (!is_array($blob) || empty($blob)) return null;
            // Try common shapes: either list of groups with aggregated stats or a flat map
            $stats = $blob;
            if (isset($blob['statistics'])) { $stats = $blob['statistics']; }
            if (is_array($stats) && isset($stats[0])) { $stats = $stats[0]; }

            // Heuristics for NBA-ish keys
            $gp = data_get($stats, 'games.played') ?? data_get($stats, 'games.appearences') ?? data_get($stats, 'games');
            $pts = data_get($stats, 'points.average') ?? data_get($stats, 'pointsPerGame') ?? data_get($stats, 'points');
            $reb = data_get($stats, 'rebounds.average') ?? data_get($stats, 'reboundsPerGame') ?? data_get($stats, 'rebounds');
            $ast = data_get($stats, 'assists.average') ?? data_get($stats, 'assistsPerGame') ?? data_get($stats, 'assists');
            $stl = data_get($stats, 'steals.average') ?? data_get($stats, 'stealsPerGame') ?? data_get($stats, 'steals');
            $blk = data_get($stats, 'blocks.average') ?? data_get($stats, 'blocksPerGame') ?? data_get($stats, 'blocks');
            $tov = data_get($stats, 'turnovers.average') ?? data_get($stats, 'turnoversPerGame') ?? data_get($stats, 'turnovers');
            $mpg = data_get($stats, 'minutes.average') ?? data_get($stats, 'minutesPerGame') ?? data_get($stats, 'minutes');

            return [
                'gp' => $gp,
                'ppg' => $pts,
                'rpg' => $reb,
                'apg' => $ast,
                'spg' => $stl,
                'bpg' => $blk,
                'tpg' => $tov,
                'mpg' => $mpg,
            ];
        };

        return response()->json([
            'ok' => true,
            'current' => $avgFrom($curr),
            'previous' => $avgFrom($prev),
        ]);
    }

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
