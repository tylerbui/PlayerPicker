<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Team;
use App\Models\League;
use Illuminate\Support\Str;

class FetchNcaaLogos extends Command
{
    protected $signature = 'ncaa:fetch-logos 
                            {gender=all : Gender (men, women, all)}';

    protected $description = 'Fetch NCAA team logos from ESPN API';

    protected int $updatedCount = 0;
    protected int $notFoundCount = 0;
    protected int $errorCount = 0;

    public function handle()
    {
        $gender = $this->argument('gender');
        $genders = $gender === 'all' ? ['men', 'women'] : [$gender];

        $this->info('🎨 Fetching NCAA team logos from ESPN...');
        $this->newLine();

        foreach ($genders as $g) {
            $this->fetchLogosForGender($g);
        }

        $this->newLine();
        $this->info('✅ Logo fetch completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated with logo', $this->updatedCount],
                ['Not found on ESPN', $this->notFoundCount],
                ['Errors', $this->errorCount],
            ]
        );

        return Command::SUCCESS;
    }

    protected function fetchLogosForGender(string $gender)
    {
        $genderLabel = ucfirst($gender);
        $this->info("📥 Fetching {$genderLabel}'s Basketball logos...");

        // Get ESPN teams
        $espnSport = $gender === 'men' ? 'mens-college-basketball' : 'womens-college-basketball';
        $espnTeams = $this->fetchEspnTeams($espnSport);

        if (empty($espnTeams)) {
            $this->error("  ❌ Failed to fetch teams from ESPN");
            $this->errorCount++;
            return;
        }

        $this->info("  Found " . count($espnTeams) . " teams on ESPN");

        // Get our NCAA D1 teams
        $leagueSlug = "ncaa-{$gender}s-d1";
        $league = League::where('slug', $leagueSlug)->first();

        if (!$league) {
            $this->error("  ❌ League '{$leagueSlug}' not found");
            return;
        }

        $teams = Team::where('league_id', $league->id)->get();
        $this->info("  Processing " . $teams->count() . " teams...");

        $bar = $this->output->createProgressBar($teams->count());
        $bar->start();

        foreach ($teams as $team) {
            $bar->advance();
            
            // Try to match team by name
            $espnTeam = $this->findEspnTeamByName($team->name, $espnTeams);

            if ($espnTeam && isset($espnTeam['logos'][0]['href'])) {
                $espnId = $espnTeam['id'] ?? null;
                
                // Check if another team already has this ESPN ID
                if ($espnId && Team::where('espn_team_id', $espnId)->where('id', '!=', $team->id)->exists()) {
                    // Skip - another team already matched to this ESPN team
                    $this->notFoundCount++;
                    continue;
                }
                
                $team->logo = $espnTeam['logos'][0]['href'];
                $team->espn_team_id = $espnId;
                $team->save();
                $this->updatedCount++;
            } else {
                $this->notFoundCount++;
            }
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function fetchEspnTeams(string $sport): array
    {
        try {
            $url = "https://site.api.espn.com/apis/site/v2/sports/basketball/{$sport}/teams";
            $response = Http::timeout(30)->get($url, ['limit' => 500]); // Get all teams

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $teams = [];

            // Parse ESPN response structure
            foreach (data_get($data, 'sports', []) as $sportData) {
                foreach (data_get($sportData, 'leagues', []) as $league) {
                    foreach (data_get($league, 'teams', []) as $teamWrapper) {
                        $team = $teamWrapper['team'] ?? $teamWrapper;
                        $teams[] = $team;
                    }
                }
            }

            return $teams;
        } catch (\Exception $e) {
            $this->error("ESPN API error: " . $e->getMessage());
            return [];
        }
    }

    protected function findEspnTeamByName(string $teamName, array $espnTeams): ?array
    {
        // Normalize team name for matching
        $normalizedName = $this->normalizeTeamName($teamName);

        foreach ($espnTeams as $espnTeam) {
            $espnLocation = $this->normalizeTeamName($espnTeam['location'] ?? '');
            $espnDisplayName = $this->normalizeTeamName($espnTeam['displayName'] ?? '');
            $espnName = $this->normalizeTeamName($espnTeam['name'] ?? '');

            // Try multiple matching strategies
            if ($normalizedName === $espnLocation ||
                $normalizedName === $espnDisplayName ||
                $normalizedName === $espnName ||
                str_contains($espnDisplayName, $normalizedName) ||
                str_contains($normalizedName, $espnLocation)) {
                return $espnTeam;
            }
        }

        return null;
    }

    protected function normalizeTeamName(string $name): string
    {
        // Remove common suffixes and normalize
        $name = str_replace(['St.', 'St', 'State'], 'State', $name);
        $name = preg_replace('/\s+/', ' ', $name); // Normalize whitespace
        $name = strtolower(trim($name));
        return $name;
    }
}
