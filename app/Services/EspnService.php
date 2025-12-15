<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EspnService
{
    // Small TTLs for near-live data
    protected int $scoreboardTtl = 10;   // seconds
    protected int $summaryTtl    = 5;    // seconds

    protected string $scoreboardUrl = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba/scoreboard';
    protected string $summaryUrl    = 'https://site.web.api.espn.com/apis/site/v2/sports/basketball/nba/summary';

    /**
     * Fetch NBA scoreboard for a date (YYYYMMDD). ESPN uses YYYYMMDD without dashes.
     */
    public function getScoreboard(?string $yyyymmdd = null): ?array
    {
        $yyyymmdd = $yyyymmdd ?: now()->format('Ymd');
        $cacheKey = "espn:nba:scoreboard:{$yyyymmdd}";

        return Cache::remember($cacheKey, $this->scoreboardTtl, function () use ($yyyymmdd) {
            try {
                $resp = Http::withOptions(['verify' => env('HTTP_VERIFY_SSL', true)])
                    ->get($this->scoreboardUrl, ['dates' => $yyyymmdd]);

                if ($resp->successful()) {
                    return $resp->json();
                }
                Log::warning('ESPN scoreboard request failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return null;
            } catch (\Throwable $e) {
                Log::error('ESPN scoreboard exception', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Fetch per-game summary/box score.
     */
    public function getGameSummary(string $eventId): ?array
    {
        $cacheKey = "espn:nba:summary:{$eventId}";
        return Cache::remember($cacheKey, $this->summaryTtl, function () use ($eventId) {
            try {
                $resp = Http::withOptions(['verify' => env('HTTP_VERIFY_SSL', true)])
                    ->get($this->summaryUrl, ['event' => $eventId]);
                if ($resp->successful()) {
                    return $resp->json();
                }
                Log::warning('ESPN summary request failed', ['event' => $eventId, 'status' => $resp->status()]);
                return null;
            } catch (\Throwable $e) {
                Log::error('ESPN summary exception', ['event' => $eventId, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Try to find today's event ID for a given team abbreviation (e.g., LAL).
     * Optionally require the game to be live (state === 'in').
     */
    public function findTodayEventForTeamAbbr(string $teamAbbr, bool $requireLive = false): ?string
    {
        $board = $this->getScoreboard();
        if (!$board || empty($board['events'])) {
            return null;
        }

        foreach ($board['events'] as $event) {
            $competitions = $event['competitions'] ?? [];
            if (empty($competitions)) continue;
            $comp = $competitions[0];
            $state = data_get($event, 'status.type.state'); // 'pre', 'in', 'post'
            if ($requireLive && $state !== 'in') continue;

            foreach ($comp['competitors'] ?? [] as $competitor) {
                $abbr = data_get($competitor, 'team.abbreviation');
                if ($abbr && Str::upper($abbr) === Str::upper($teamAbbr)) {
                    return (string)($event['id'] ?? null);
                }
            }
        }
        return null;
    }

    /**
     * Extract a single player line from a game summary by name and team abbreviation.
     * Returns a compact array or null if not found.
     */
    public function extractPlayerLiveLine(array $summary, string $playerFullName, string $teamAbbr): ?array
    {
        $boxes = data_get($summary, 'boxscore.teams', []);
        if (empty($boxes)) return null;

        // ESPN summary has 'boxscore.players' per team in some variants. Handle both structures.
        $byTeams = data_get($summary, 'boxscore.players');
        if (is_array($byTeams)) {
            foreach ($byTeams as $teamBlock) {
                $abbr = data_get($teamBlock, 'team.abbreviation');
                if (Str::upper((string)$abbr) !== Str::upper($teamAbbr)) continue;
                foreach (data_get($teamBlock, 'statistics', []) as $statGroup) {
                    foreach (data_get($statGroup, 'athletes', []) as $ath) {
                        $name = data_get($ath, 'athlete.displayName') ?? data_get($ath, 'athlete.shortName');
                        if ($this->namesLikelyMatch($name, $playerFullName)) {
                            return $this->normalizeAthleteLine($ath);
                        }
                    }
                }
            }
        }

        // Fallback: some variants have 'boxscore.teams[*].players'
        foreach ($boxes as $teamBox) {
            $abbr = data_get($teamBox, 'team.abbreviation');
            if (Str::upper((string)$abbr) !== Str::upper($teamAbbr)) continue;
            foreach (data_get($teamBox, 'players', []) as $group) {
                foreach (data_get($group, 'statistics', []) as $statGroup) {
                    foreach (data_get($statGroup, 'athletes', []) as $ath) {
                        $name = data_get($ath, 'athlete.displayName') ?? data_get($ath, 'athlete.shortName');
                        if ($this->namesLikelyMatch($name, $playerFullName)) {
                            return $this->normalizeAthleteLine($ath);
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function namesLikelyMatch(?string $espnName, string $fullName): bool
    {
        if (!$espnName) return false;
        $a = Str::of($espnName)->lower()->replace(".", '')->replace("'", '')->squish();
        $b = Str::of($fullName)->lower()->replace(".", '')->replace("'", '')->squish();
        if ($a == $b) return true;
        // loose check: first and last
        [$bf, $bl] = array_pad(explode(' ', $b, 2), 2, '');
        return Str::contains($a, $bf) && Str::contains($a, $bl);
    }

    protected function normalizeAthleteLine(array $ath): array
    {
        $stats = collect(data_get($ath, 'stats', []))->keyBy('name');
        $line = [
            'athleteId'   => data_get($ath, 'athlete.id'),
            'displayName' => data_get($ath, 'athlete.displayName'),
            'shortName'   => data_get($ath, 'athlete.shortName'),
            'starter'     => (bool) data_get($ath, 'starter', false),
            'didNotPlay'  => (bool) data_get($ath, 'didNotPlay', false),
            'ejected'     => (bool) data_get($ath, 'ejected', false),
            'minutes'     => data_get($ath, 'minutes') ?? data_get($ath, 'statistics.0.minutes'),
            'raw'         => $ath,
        ];
        // Common NBA box stats (keys vary across feeds); map if present
        $line['pts'] = data_get($ath, 'points') ?? data_get($stats->get('points'), 'value');
        $line['reb'] = data_get($stats->get('rebounds'), 'value');
        $line['ast'] = data_get($stats->get('assists'), 'value');
        $line['stl'] = data_get($stats->get('steals'), 'value');
        $line['blk'] = data_get($stats->get('blocks'), 'value');
        $line['tov'] = data_get($stats->get('turnovers'), 'value');
        $line['fg']  = data_get($stats->get('fieldGoalsMade'), 'value') . '/' . data_get($stats->get('fieldGoalsAttempted'), 'value');
        $line['fg3'] = data_get($stats->get('threePointFieldGoalsMade'), 'value') . '/' . data_get($stats->get('threePointFieldGoalsAttempted'), 'value');
        $line['ft']  = data_get($stats->get('freeThrowsMade'), 'value') . '/' . data_get($stats->get('freeThrowsAttempted'), 'value');
        return $line;
    }
}