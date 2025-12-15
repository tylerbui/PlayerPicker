<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SportsDbService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.sportsdb.key', '3');
        $this->baseUrl = rtrim(config('services.sportsdb.base_url'), '/');
    }

    protected function url(string $endpoint): string
    {
        return sprintf('%s/%s/%s', $this->baseUrl, $this->apiKey, ltrim($endpoint, '/'));
    }

    /**
     * Search players by name
     * https://www.thesportsdb.com/api/v1/json/{APIKEY}/searchplayers.php?p={player_name}
     */
    public function searchPlayers(string $name): array
    {
        $cacheKey = 'sportsdb_search_' . md5($name);
        return Cache::remember($cacheKey, 3600, function () use ($name) {
            try {
                $res = Http::get($this->url('searchplayers.php'), ['p' => $name]);
                if ($res->successful()) {
                    return $res->json()['player'] ?? [];
                }
            } catch (\Throwable $e) {
                Log::warning('SportsDB search failed', ['e' => $e->getMessage()]);
            }
            return [];
        });
    }

    /**
     * Lookup player by SportsDB player id
     * https://www.thesportsdb.com/api/v1/json/{APIKEY}/lookupplayer.php?id={id}
     */
    public function lookupPlayer(int|string $sportsDbPlayerId): ?array
    {
        $cacheKey = 'sportsdb_lookup_' . $sportsDbPlayerId;
        return Cache::remember($cacheKey, 3600, function () use ($sportsDbPlayerId) {
            try {
                $res = Http::get($this->url('lookupplayer.php'), ['id' => $sportsDbPlayerId]);
                if ($res->successful()) {
                    return $res->json()['players'][0] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('SportsDB lookup failed', ['e' => $e->getMessage()]);
            }
            return null;
        });
    }

    /**
     * Best-effort enrichment by name: returns first matching player object
     */
    public function enrichByName(string $fullName): ?array
    {
        $players = $this->searchPlayers($fullName);
        return $players[0] ?? null;
    }
}
