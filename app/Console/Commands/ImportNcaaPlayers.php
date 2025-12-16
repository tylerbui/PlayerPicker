<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Team;
use App\Models\Player;
use App\Models\League;
use Illuminate\Support\Str;

class ImportNcaaPlayers extends Command
{
    protected $signature = 'ncaa:import-players 
                            {gender=all : Gender (men, women, all)}
                            {--limit= : Limit number of teams to process}
                            {--team= : Specific team slug to import}';

    protected $description = 'Import NCAA players from ESPN API';

    protected int $playersImported = 0;
    protected int $playersUpdated = 0;
    protected int $teamsProcessed = 0;
    protected int $teamsSkipped = 0;
    protected int $errorCount = 0;

    public function handle()
    {
        $gender = $this->argument('gender');
        $limit = $this->option('limit');
        $teamSlug = $this->option('team');

        $this->info('🏀 Starting NCAA players import from ESPN...');
        $this->newLine();

        if ($teamSlug) {
            // Import specific team
            $team = Team::where('slug', $teamSlug)->first();
            if (!$team) {
                $this->error("Team '{$teamSlug}' not found");
                return Command::FAILURE;
            }
            $this->importTeamRoster($team);
        } else {
            // Import by gender
            $genders = $gender === 'all' ? ['men', 'women'] : [$gender];
            
            foreach ($genders as $g) {
                $this->importPlayersForGender($g, $limit);
            }
        }

        $this->newLine();
        $this->info('✅ Player import completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Players imported (new)', $this->playersImported],
                ['Players updated', $this->playersUpdated],
                ['Teams processed', $this->teamsProcessed],
                ['Teams skipped (no ESPN ID)', $this->teamsSkipped],
                ['Errors', $this->errorCount],
            ]
        );

        return Command::SUCCESS;
    }

    protected function importPlayersForGender(string $gender, ?int $limit)
    {
        $genderLabel = ucfirst($gender);
        $this->info("📥 Importing {$genderLabel}'s Basketball players...");

        // Get teams with ESPN IDs
        $leagueSlug = "ncaa-{$gender}s-d1";
        $league = League::where('slug', $leagueSlug)->first();

        if (!$league) {
            $this->error("  ❌ League '{$leagueSlug}' not found");
            return;
        }

        $query = Team::where('league_id', $league->id)
            ->whereNotNull('espn_team_id');

        if ($limit) {
            $query->limit($limit);
        }

        $teams = $query->get();
        
        $teamsWithoutId = Team::where('league_id', $league->id)
            ->whereNull('espn_team_id')
            ->count();

        $this->info("  Found " . $teams->count() . " teams with ESPN IDs");
        $this->warn("  {$teamsWithoutId} teams don't have ESPN IDs (will be skipped)");
        $this->newLine();

        $bar = $this->output->createProgressBar($teams->count());
        $bar->start();

        foreach ($teams as $team) {
            $bar->advance();
            $this->importTeamRoster($team);
            
            // Small delay to respect rate limits
            usleep(100000); // 0.1 second
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function importTeamRoster(Team $team)
    {
        if (!$team->espn_team_id) {
            $this->teamsSkipped++;
            return;
        }

        try {
            $roster = $this->fetchEspnRoster($team->espn_team_id);

            if (empty($roster)) {
                $this->teamsSkipped++;
                return;
            }

            foreach ($roster as $athlete) {
                $this->importPlayer($athlete, $team);
            }

            $this->teamsProcessed++;
        } catch (\Exception $e) {
            $this->errorCount++;
            \Log::error("Failed to import roster for team {$team->name}", [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function fetchEspnRoster(string $espnTeamId): array
    {
        try {
            // Determine sport based on team's league
            $sport = 'mens-college-basketball'; // Default, can be enhanced
            
            $url = "https://site.api.espn.com/apis/site/v2/sports/basketball/{$sport}/teams/{$espnTeamId}/roster";
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            return $data['athletes'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function importPlayer(array $athleteData, Team $team)
    {
        $espnId = $athleteData['id'] ?? null;
        if (!$espnId) {
            return;
        }

        // Extract name
        $fullName = $athleteData['fullName'] ?? $athleteData['displayName'] ?? '';
        $firstName = $athleteData['firstName'] ?? '';
        $lastName = $athleteData['lastName'] ?? '';

        // If firstName/lastName not provided, try to split fullName
        if (empty($firstName) && !empty($fullName)) {
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }

        $slug = Str::slug($fullName . '-' . $team->slug);

        // Check if player exists
        $player = Player::where('team_id', $team->id)
            ->where('espn_athlete_id', $espnId)
            ->first();

        $playerData = [
            'team_id' => $team->id,
            'api_id' => 'espn-' . $espnId, // Use ESPN ID as api_id
            'espn_athlete_id' => $espnId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'slug' => $slug,
            'position' => $athleteData['position']['abbreviation'] ?? $athleteData['position']['name'] ?? null,
            'number' => $athleteData['jersey'] ?? null,
            'height' => $this->parseHeight($athleteData['height'] ?? null),
            'weight' => $athleteData['weight'] ?? null,
            'photo' => $athleteData['headshot']['href'] ?? $athleteData['headshot'] ?? null,
            'extra_data' => [
                'espn_data' => $athleteData,
                'experience' => $athleteData['experience']['displayValue'] ?? null,
            ],
            'is_active' => true,
            'synced_at' => now(),
        ];

        if ($player) {
            $player->update($playerData);
            $this->playersUpdated++;
        } else {
            Player::create($playerData);
            $this->playersImported++;
        }
    }

    protected function parseHeight(?string $height): ?int
    {
        if (!$height) {
            return null;
        }

        // ESPN height format: "6' 8\"" or "6-8"
        if (preg_match("/([0-9]+)['-]\s*([0-9]+)/", $height, $matches)) {
            $feet = (int)$matches[1];
            $inches = (int)$matches[2];
            return ($feet * 12) + $inches; // Total inches
        }

        return null;
    }
}
