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
    protected int $teamsTtl      = 3600; // 1 hour
    protected int $rosterTtl     = 600;  // 10 minutes

    protected string $scoreboardUrl = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba/scoreboard';
protected string $summaryUrl    = 'https://site.web.api.espn.com/apis/site/v2/sports/basketball/nba/summary';
    protected string $teamsUrl      = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba/teams';

    /**
     * Find recent completed events for a team (by abbreviation), scanning backward day by day.
     */
    public function findRecentEventsForTeamAbbr(string $teamAbbr, int $limit = 5, int $maxDaysBack = 30): array
    {
        $found = [];
        for ($i = 0; $i < $maxDaysBack && count($found) < $limit; $i++) {
            $date = now()->subDays($i)->format('Ymd');
            $board = $this->getScoreboard($date);
            if (!$board || empty($board['events'])) continue;
            foreach ($board['events'] as $event) {
                $state = data_get($event, 'status.type.state');
                if ($state !== 'post') continue;
                $comp = data_get($event, 'competitions.0');
                $competitors = data_get($comp, 'competitors', []);
                $foundTeam = null; $opp = null;
                foreach ($competitors as $c) {
                    $abbr = data_get($c, 'team.abbreviation');
                    if ($abbr && strcasecmp($abbr, $teamAbbr) === 0) {
                        $foundTeam = $c; break;
                    }
                }
                if (!$foundTeam) continue;
                foreach ($competitors as $c) {
                    if ($c === $foundTeam) continue; $opp = $c; break;
                }
                $isHome = !!data_get($foundTeam, 'homeAway') === 'home';
                $ourScore = (int) data_get($foundTeam, 'score');
                $oppScore = (int) data_get($opp, 'score');
                $result = $ourScore > $oppScore ? 'W' : 'L';

                $logo = data_get($opp, 'team.logo') ?? data_get($opp, 'team.logos.0.href');

                $found[] = [
                    'eventId' => (string) data_get($event, 'id'),
                    'date' => data_get($event, 'date'),
                    'state' => $state,
                    'homeAway' => data_get($foundTeam, 'homeAway'),
                    'opponent' => [
                        'name' => data_get($opp, 'team.displayName'),
                        'abbreviation' => data_get($opp, 'team.abbreviation'),
                        'logo' => $logo,
                    ],
                    'score' => [ 'team' => $ourScore, 'opp' => $oppScore, 'result' => $result ],
                ];
                if (count($found) >= $limit) break 2;
            }
        }
        return $found;
    }

    /**
     * Convenience: get player line for a given event.
     */
    public function getPlayerLineForEvent(string $eventId, string $playerFullName, string $teamAbbr): ?array
    {
        $summary = $this->getGameSummary($eventId);
        if (!$summary) return null;
        return $this->extractPlayerLiveLine($summary, $playerFullName, $teamAbbr);
    }

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
     * Fetch list of NBA teams (id, abbr, displayName, logos...).
     */
    public function getTeams(): array
    {
        return Cache::remember('espn:nba:teams', $this->teamsTtl, function () {
            try {
                $resp = Http::withOptions(['verify' => env('HTTP_VERIFY_SSL', true)])
                    ->get($this->teamsUrl);
                if (!$resp->successful()) return [];
                $data = $resp->json();
                $teams = [];
                foreach (data_get($data, 'sports', []) as $sport) {
                    foreach (data_get($sport, 'leagues', []) as $league) {
                        foreach (data_get($league, 'teams', []) as $t) {
                            $team = $t['team'] ?? $t; // handle both shapes
                            $teams[] = $team;
                        }
                    }
                }
                // Some responses are flat under .teams
                if (empty($teams)) {
                    foreach (data_get($data, 'teams', []) as $t) {
                        $team = $t['team'] ?? $t;
                        $teams[] = $team;
                    }
                }
                return $teams;
            } catch (\Throwable $e) {
                Log::error('ESPN teams exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Fetch a team roster by ESPN team id or abbreviation.
     */
    public function getTeamRoster(string $teamIdOrAbbr): array
    {
        $cacheKey = "espn:nba:roster:{$teamIdOrAbbr}";
        return Cache::remember($cacheKey, $this->rosterTtl, function () use ($teamIdOrAbbr) {
            try {
                $url = rtrim($this->teamsUrl, '/')."/{$teamIdOrAbbr}/roster";
                $resp = Http::withOptions(['verify' => env('HTTP_VERIFY_SSL', true)])
                    ->get($url, ['lang' => 'en', 'region' => 'us']);
                if (!$resp->successful()) return [];
                $data = $resp->json();
                // Expected shape: athletes[*] with .id, .fullName, .position, .jersey, .headshot.href, etc.
                return data_get($data, 'athletes', []) ?? [];
            } catch (\Throwable $e) {
                Log::error('ESPN roster exception', ['team' => $teamIdOrAbbr, 'error' => $e->getMessage()]);
                return [];
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
     * Try to find today's event for a player by name, scanning all games today.
     * Returns ['eventId' => string, 'teamAbbr' => string] or null.
     */
    public function findTodayEventForPlayerName(string $playerFullName, bool $requireLive = false): ?array
    {
        $board = $this->getScoreboard();
        if (!$board || empty($board['events'])) return null;
        foreach ($board['events'] as $event) {
            $state = data_get($event, 'status.type.state');
            if ($requireLive && $state !== 'in') continue;
            $eventId = (string) data_get($event, 'id');
            $summary = $this->getGameSummary($eventId);
            if (!$summary) continue;
            $match = $this->extractPlayerFromSummaryAnyTeam($summary, $playerFullName);
            if ($match) {
                return ['eventId' => $eventId, 'teamAbbr' => $match['teamAbbr']];
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
                            return $this->normalizeAthleteLine($ath, $statGroup);
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
                            return $this->normalizeAthleteLine($ath, $statGroup);
                        }
                    }
                }
            }
        }

        return null;
    }

    public function extractPlayerFromSummaryAnyTeam(array $summary, string $playerFullName): ?array
    {
        // First, try the 'players' structure
        $byTeams = data_get($summary, 'boxscore.players');
        if (is_array($byTeams)) {
            foreach ($byTeams as $teamBlock) {
                $teamAbbr = data_get($teamBlock, 'team.abbreviation');
                foreach (data_get($teamBlock, 'statistics', []) as $statGroup) {
                    foreach (data_get($statGroup, 'athletes', []) as $ath) {
                        $name = data_get($ath, 'athlete.displayName') ?? data_get($ath, 'athlete.shortName');
                        if ($this->namesLikelyMatch($name, $playerFullName)) {
                            return [
                                'teamAbbr' => $teamAbbr,
                                'line' => $this->normalizeAthleteLine($ath, $statGroup)
                            ];
                        }
                    }
                }
            }
        }

        // Fallback to 'teams[*].players'
        foreach (data_get($summary, 'boxscore.teams', []) as $teamBox) {
            $teamAbbr = data_get($teamBox, 'team.abbreviation');
            foreach (data_get($teamBox, 'players', []) as $group) {
                foreach (data_get($group, 'statistics', []) as $statGroup) {
                    foreach (data_get($statGroup, 'athletes', []) as $ath) {
                        $name = data_get($ath, 'athlete.displayName') ?? data_get($ath, 'athlete.shortName');
                        if ($this->namesLikelyMatch($name, $playerFullName)) {
                            return [
                                'teamAbbr' => $teamAbbr,
                                'line' => $this->normalizeAthleteLine($ath, $statGroup)
                            ];
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

    protected function normalizeAthleteLine(array $ath, ?array $group = null): array
    {
        // Build a key->value map of stats from possible shapes
        $statsArr = data_get($ath, 'stats', []);
        $stats = collect();
        if (is_array($statsArr)) {
            // Variant A: list of objects with name/value
            if (!empty($statsArr) && is_array($statsArr[0] ?? null) && array_key_exists('name', $statsArr[0])) {
                $stats = collect($statsArr)->keyBy('name');
            }
            // Variant B: parallel arrays using group labels/names and athlete.stats values
            elseif ($group && (is_array($group['labels'] ?? null) || is_array($group['names'] ?? null))) {
                $keys = $group['labels'] ?? $group['names'];
                $vals = $statsArr;
                $map = [];
                foreach ($keys as $i => $k) {
                    $map[$k] = $vals[$i] ?? null;
                }
                $stats = collect($map);
            }
        }

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
        // Helpers to pull by multiple aliases
        $get = function($keys) use ($stats) {
            foreach ((array)$keys as $k) {
                if ($stats instanceof \Illuminate\Support\Collection) {
                    // support both object shape {name,value} and direct value
                    $obj = $stats->get($k);
                    if (is_array($obj) && array_key_exists('value', $obj)) return $obj['value'];
                    if ($obj !== null) return $obj;
                }
            }
            return null;
        };

        // Common NBA box stats (keys vary across feeds); map with aliases
        $line['pts'] = data_get($ath, 'points') ?? $get(['PTS','Points','points']);
        $line['reb'] = $get(['REB','Rebounds','rebounds','totalRebounds']);
        $line['ast'] = $get(['AST','Assists','assists']);
        $line['stl'] = $get(['STL','Steals','steals']);
        $line['blk'] = $get(['BLK','Blocks','blocks']);
        $line['tov'] = $get(['TO','Turnovers','turnovers']);

        $fgm = $get(['FGM','fieldGoalsMade']);
        $fga = $get(['FGA','fieldGoalsAttempted']);
        $tpm = $get(['3PM','threePointFieldGoalsMade','threePointersMade']);
        $tpa = $get(['3PA','threePointFieldGoalsAttempted','threePointersAttempted']);
        $ftm = $get(['FTM','freeThrowsMade']);
        $fta = $get(['FTA','freeThrowsAttempted']);

        $line['fg']  = ($fgm!==null && $fga!==null) ? ($fgm.'/'.$fga) : null;
        $line['fg3'] = ($tpm!==null && $tpa!==null) ? ($tpm.'/'.$tpa) : null;
        $line['ft']  = ($ftm!==null && $fta!==null) ? ($ftm.'/'.$fta) : null;
        return $line;
    }
    /**
     * Find recent games for a player by name (independent of stored team), with lines.
     * Cached briefly to limit ESPN calls.
     */
    public function findRecentGamesByPlayerName(string $playerFullName, int $limit = 5, int $maxDaysBack = 30): array
    {
        $cacheKey = 'espn:nba:recentByName:'.md5($playerFullName).":{$limit}:{$maxDaysBack}";
        return Cache::remember($cacheKey, 300, function () use ($playerFullName, $limit, $maxDaysBack) {
            $found = [];
            for ($i = 0; $i < $maxDaysBack && count($found) < $limit; $i++) {
                $date = now()->subDays($i)->format('Ymd');
                $board = $this->getScoreboard($date);
                if (!$board || empty($board['events'])) continue;
                foreach ($board['events'] as $event) {
                    $state = data_get($event, 'status.type.state');
                    if ($state !== 'post') continue;
                    $eventId = (string) data_get($event, 'id');
                    $summary = $this->getGameSummary($eventId);
                    if (!$summary) continue;
                    $match = $this->extractPlayerFromSummaryAnyTeam($summary, $playerFullName);
                    if (!$match) continue;
                    $teamAbbr = $match['teamAbbr'];

                    // Derive opponent/homeAway/score from header competitors
                    $comp = data_get($summary, 'header.competitions.0');
                    $competitors = data_get($comp, 'competitors', []);
                    $us = null; $opp = null;
                    foreach ($competitors as $c) {
                        if (strcasecmp(data_get($c, 'team.abbreviation', ''), (string)$teamAbbr) === 0) { $us = $c; }
                        else { $opp = $c; }
                    }
                    if (!$us || !$opp) continue;

                    $logo = data_get($opp, 'team.logo') ?? data_get($opp, 'team.logos.0.href');
                    $ourScore = (int) data_get($us, 'score');
                    $oppScore = (int) data_get($opp, 'score');
                    $result = $ourScore > $oppScore ? 'W' : 'L';

                    $found[] = [
                        'eventId' => $eventId,
                        'date' => data_get($event, 'date'),
                        'state' => $state,
                        'homeAway' => data_get($us, 'homeAway'),
                        'opponent' => [
                            'name' => data_get($opp, 'team.displayName'),
                            'abbreviation' => data_get($opp, 'team.abbreviation'),
                            'logo' => $logo,
                        ],
                        'score' => [ 'team' => $ourScore, 'opp' => $oppScore, 'result' => $result ],
                        'line' => $match['line'],
                    ];

                    if (count($found) >= $limit) break 2;
                }
            }
            return $found;
        });
    }
}
