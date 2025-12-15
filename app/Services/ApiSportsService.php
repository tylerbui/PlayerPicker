<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiSportsService
{
    protected string $apiKey;
    protected array $urls;
    protected string $currentSport = 'football'; // default
    protected int $cacheDuration = 3600; // 1 hour cache

    public function __construct()
    {
        $this->apiKey = config('services.api_sports.key');
        $this->urls = config('services.api_sports.urls');
    }

    /**
     * Set the sport to use for API calls
     */
    public function setSport(string $sport): self
    {
        if (!isset($this->urls[$sport])) {
            throw new \InvalidArgumentException("Sport '{$sport}' is not configured.");
        }
        
        $this->currentSport = $sport;
        return $this;
    }

    /**
     * Get the base URL for the current sport
     */
    protected function getBaseUrl(): string
    {
        return $this->urls[$this->currentSport];
    }

    /**
     * Make a request to API-Sports
     */
    protected function makeRequest(string $endpoint, array $params = [])
    {
        try {
            $baseUrl = $this->getBaseUrl();
            
            $response = Http::withOptions([
                // NOTE: If you see SSL errors in dev, you can disable verification. Do not use in prod.
                'verify' => env('HTTP_VERIFY_SSL', true),
            ])->withHeaders([
                'x-apisports-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get("{$baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('API-Sports request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('API-Sports exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get teams by league and season
     */
    public function getTeams(int $leagueId, int $season)
    {
        $cacheKey = "api_sports_teams_{$leagueId}_{$season}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($leagueId, $season) {
            $data = $this->makeRequest('teams', [
                'league' => $leagueId,
                'season' => $season
            ]);

            return $data['response'] ?? [];
        });
    }

    /**
     * Get team by ID
     */
    public function getTeam(int $teamId)
    {
        $cacheKey = "api_sports_team_{$teamId}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($teamId) {
            $data = $this->makeRequest('teams', ['id' => $teamId]);
            return $data['response'][0] ?? null;
        });
    }

    /**
     * Get players by team and season
     */
    public function getPlayers(int $teamId, int $season)
    {
        $cacheKey = "api_sports_players_{$teamId}_{$season}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($teamId, $season) {
            $data = $this->makeRequest('players', [
                'team' => $teamId,
                'season' => $season
            ]);

            return $data['response'] ?? [];
        });
    }

    /**
     * Get player by ID
     */
    public function getPlayer(int $playerId, int $season)
    {
        $cacheKey = "api_sports_player_{$playerId}_{$season}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($playerId, $season) {
            $data = $this->makeRequest('players', [
                'id' => $playerId,
                'season' => $season
            ]);

            return $data['response'][0] ?? null;
        });
    }

    /**
     * Get leagues/competitions
     */
    public function getLeagues(?string $country = null, ?int $season = null)
    {
        $params = array_filter([
            'country' => $country,
            'season' => $season,
        ]);

        $cacheKey = "api_sports_leagues_" . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($params) {
            $data = $this->makeRequest('leagues', $params);
            return $data['response'] ?? [];
        });
    }

    /**
     * Get live fixtures/games
     */
    public function getLiveGames()
    {
        // Don't cache live data
        $data = $this->makeRequest('fixtures', ['live' => 'all']);
        return $data['response'] ?? [];
    }

    /**
     * Get fixtures by date
     */
    public function getFixturesByDate(string $date, ?int $leagueId = null)
    {
        $params = array_filter([
            'date' => $date,
            'league' => $leagueId,
        ]);

        $cacheKey = "api_sports_fixtures_" . md5(json_encode($params));

        return Cache::remember($cacheKey, 900, function () use ($params) { // 15 min cache
            $data = $this->makeRequest('fixtures', $params);
            return $data['response'] ?? [];
        });
    }

    /**
     * Search players by name
     */
    public function searchPlayers(string $name, ?int $leagueId = null, ?int $season = null)
    {
        $params = array_filter([
            'search' => $name,
            'league' => $leagueId,
            'season' => $season ?? date('Y'),
        ]);

        $data = $this->makeRequest('players', $params);
        return $data['response'] ?? [];
    }

    /**
     * Get player statistics
     */
    public function getPlayerStats(int $playerId, int $season)
    {
        $cacheKey = "api_sports_player_stats_{$playerId}_{$season}";

        return Cache::remember($cacheKey, 1800, function () use ($playerId, $season) { // 30 min cache
            $data = $this->makeRequest('players', [
                'id' => $playerId,
                'season' => $season
            ]);

            return $data['response'][0]['statistics'] ?? [];
        });
    }

    /**
     * Get player season statistics (current season)
     */
    public function getPlayerSeasonStats(int $playerId, int $season)
    {
        $cacheKey = "api_sports_player_season_{$playerId}_{$season}";

        return Cache::remember($cacheKey, 1800, function () use ($playerId, $season) { // 30 min cache
            $data = $this->makeRequest('players', [
                'id' => $playerId,
                'season' => $season
            ]);

            return $data['response'][0] ?? null;
        });
    }

    /**
     * Get player recent games (last fixtures)
     */
    public function getPlayerRecentGames(int $playerId, int $season, int $limit = 10)
    {
        $cacheKey = "api_sports_player_games_{$playerId}_{$season}_{$limit}";

        return Cache::remember($cacheKey, 900, function () use ($playerId, $season, $limit) { // 15 min cache
            // Get fixtures for this player
            $data = $this->makeRequest('fixtures/players', [
                'player' => $playerId,
                'season' => $season,
                'last' => $limit
            ]);

            return $data['response'] ?? [];
        });
    }

    /**
     * Get player career statistics
     */
    public function getPlayerCareerStats(int $playerId)
    {
        $cacheKey = "api_sports_player_career_{$playerId}";

        return Cache::remember($cacheKey, 3600, function () use ($playerId) { // 1 hour cache
            $data = $this->makeRequest('players', [
                'id' => $playerId
            ]);

            return $data['response'] ?? [];
        });
    }

    /**
     * Clear cache for specific keys
     */
    public function clearCache(string $pattern = 'api_sports_*')
    {
        // Note: This is a simple implementation
        // For production, consider using Redis tags or a better cache invalidation strategy
        Cache::flush();
    }
}
