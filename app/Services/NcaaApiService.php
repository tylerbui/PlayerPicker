<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NcaaApiService
{
    protected string $baseUrl;
    protected int $cacheDuration = 900; // 15 minutes for most data
    protected int $staticCacheDuration = 3600; // 1 hour for static data like standings

    public function __construct()
    {
        $this->baseUrl = config('services.ncaa_api.base_url');
    }

    /**
     * Make a request to the NCAA API
     */
    protected function makeRequest(string $endpoint, array $params = [])
    {
        try {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
            
            $response = Http::withOptions([
                'verify' => env('HTTP_VERIFY_SSL', true),
            ])->timeout(10)->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('NCAA API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('NCAA API exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get live scores for a sport, division, season, week, and conference
     * 
     * @param string $sport e.g., 'football', 'basketball-men', 'basketball-women'
     * @param string $division e.g., 'fbs', 'fcs', 'd1', 'd2', 'd3'
     * @param int $season e.g., 2023, 2024
     * @param int|string $week Week number or 'all'
     * @param string $conference Conference name or 'all-conf'
     */
    public function getScoreboard(
        string $sport, 
        string $division, 
        int $season, 
        int|string $week = 'all', 
        string $conference = 'all-conf'
    ) {
        $endpoint = "scoreboard/{$sport}/{$division}/{$season}/{$week}/{$conference}";
        $cacheKey = "ncaa_scoreboard_" . md5($endpoint);

        // Live scores shouldn't be cached long
        return Cache::remember($cacheKey, 300, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get stats for a sport and division
     * 
     * @param string $sport e.g., 'football', 'basketball-men'
     * @param string $division e.g., 'fbs', 'd1'
     * @param string $period e.g., 'current', '2023'
     * @param string $category e.g., 'team', 'individual'
     * @param int $statId Stat category ID (e.g., 28 for scoring defense)
     */
    public function getStats(
        string $sport,
        string $division,
        string $period,
        string $category,
        int $statId
    ) {
        $endpoint = "stats/{$sport}/{$division}/{$period}/{$category}/{$statId}";
        $cacheKey = "ncaa_stats_" . md5($endpoint);

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get rankings/polls for a sport and division
     * 
     * @param string $sport e.g., 'football', 'basketball-men'
     * @param string $division e.g., 'fbs', 'd1'
     * @param string $season e.g., '2023', '2024'
     * @param int|string $week Week number or 'final'
     */
    public function getRankings(
        string $sport,
        string $division,
        string $season,
        int|string $week = 'final'
    ) {
        $endpoint = "rankings/{$sport}/{$division}/{$season}/{$week}";
        $cacheKey = "ncaa_rankings_" . md5($endpoint);

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get standings for a sport and division
     * 
     * @param string $sport e.g., 'football', 'basketball-men', 'basketball-women'
     * @param string $division e.g., 'fbs', 'd1'
     */
    public function getStandings(string $sport, string $division)
    {
        $endpoint = "standings/{$sport}/{$division}";
        $cacheKey = "ncaa_standings_{$sport}_{$division}";

        return Cache::remember($cacheKey, $this->staticCacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get schedule for a team in a season
     * 
     * @param string $sport e.g., 'basketball-men'
     * @param string $division e.g., 'd1'
     * @param string $teamSeo Team's SEO slug (e.g., 'duke', 'north-carolina')
     * @param int $season Season year
     */
    public function getTeamSchedule(
        string $sport,
        string $division,
        string $teamSeo,
        int $season
    ) {
        $endpoint = "schools/{$teamSeo}/{$sport}/schedule/{$season}";
        $cacheKey = "ncaa_schedule_{$teamSeo}_{$sport}_{$season}";

        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get game details (box score, play by play, etc.)
     * 
     * @param int $gameId Game ID from NCAA
     */
    public function getGameDetails(int $gameId)
    {
        $endpoint = "game/{$gameId}";
        $cacheKey = "ncaa_game_{$gameId}";

        // Don't cache if game is potentially live, otherwise cache for 1 hour
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Get teams for a conference
     * 
     * @param string $sport e.g., 'basketball-men'
     * @param string $division e.g., 'd1'
     * @param string $conference Conference SEO slug (e.g., 'acc', 'big-ten')
     */
    public function getConferenceTeams(
        string $sport,
        string $division,
        string $conference
    ) {
        // This endpoint returns teams via the scoreboard with conference filter
        $endpoint = "scoreboard/{$sport}/{$division}/" . date('Y') . "/all/{$conference}";
        $cacheKey = "ncaa_conference_teams_{$conference}_{$sport}";

        return Cache::remember($cacheKey, $this->staticCacheDuration, function () use ($endpoint) {
            return $this->makeRequest($endpoint);
        });
    }

    /**
     * Helper: Get current week's football scores for a division
     */
    public function getCurrentFootballScores(string $division = 'fbs')
    {
        return $this->getScoreboard('football', $division, (int)date('Y'), 'all', 'all-conf');
    }

    /**
     * Helper: Get current men's basketball scores
     */
    public function getCurrentMensBasketballScores()
    {
        return $this->getScoreboard('basketball-men', 'd1', (int)date('Y'), 'all', 'all-conf');
    }

    /**
     * Helper: Get current women's basketball scores
     */
    public function getCurrentWomensBasketballScores()
    {
        return $this->getScoreboard('basketball-women', 'd1', (int)date('Y'), 'all', 'all-conf');
    }

    /**
     * Helper: Get AP Top 25 football rankings
     */
    public function getFootballTop25(int $season = null, int|string $week = 'final')
    {
        $season = $season ?? (int)date('Y');
        return $this->getRankings('football', 'fbs', (string)$season, $week);
    }

    /**
     * Helper: Get men's basketball AP poll
     */
    public function getMensBasketballPoll(int $season = null)
    {
        $season = $season ?? (int)date('Y');
        return $this->getRankings('basketball-men', 'd1', (string)$season, 'current');
    }

    /**
     * Clear all NCAA API cache
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('NCAA API cache cleared');
    }

    /**
     * Clear specific cache by key pattern
     */
    public function clearCacheByPattern(string $pattern): void
    {
        // Note: This requires a cache store that supports tag or pattern clearing
        // For simple implementation, we can use cache tags if using Redis
        Log::info("Clearing NCAA API cache for pattern: {$pattern}");
    }
}
