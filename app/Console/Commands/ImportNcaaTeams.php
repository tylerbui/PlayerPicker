<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NcaaApiService;
use App\Models\League;
use App\Models\Team;
use Illuminate\Support\Str;

class ImportNcaaTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ncaa:import-teams 
                            {sport=basketball : Sport type (basketball)}
                            {gender=all : Gender (men, women, all)}
                            {division=all : Division (d1, d2, d3, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import NCAA teams from the NCAA API';

    protected NcaaApiService $ncaaService;
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $errorCount = 0;

    /**
     * Execute the console command.
     */
    public function handle(NcaaApiService $ncaaService)
    {
        $this->ncaaService = $ncaaService;
        
        $sport = $this->argument('sport');
        $gender = $this->argument('gender');
        $division = $this->argument('division');

        $this->info("🏀 Starting NCAA {$sport} teams import...");
        $this->info("Gender: {$gender}, Division: {$division}");
        $this->newLine();

        if ($sport === 'basketball') {
            $this->importBasketballTeams($gender, $division);
        } else {
            $this->error("Sport '{$sport}' is not yet supported.");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Import completed!");
        $this->info("📊 Results:");
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported (new)', $this->importedCount],
                ['Updated (existing)', $this->updatedCount],
                ['Errors', $this->errorCount],
                ['Total', $this->importedCount + $this->updatedCount],
            ]
        );

        return Command::SUCCESS;
    }

    protected function importBasketballTeams(string $gender, string $divisionFilter)
    {
        $genders = $gender === 'all' ? ['men', 'women'] : [$gender];
        $divisions = $divisionFilter === 'all' ? ['d1', 'd2', 'd3'] : [$divisionFilter];

        foreach ($genders as $g) {
            foreach ($divisions as $div) {
                $this->importDivision($g, $div);
            }
        }
    }

    protected function importDivision(string $gender, string $division)
    {
        $divisionUpper = strtoupper($division);
        $genderLabel = ucfirst($gender);
        
        $this->info("📥 Importing NCAA {$genderLabel}'s Basketball ({$divisionUpper})...");

        // Find the league
        $leagueSlug = "ncaa-{$gender}s-{$division}";
        $league = League::where('slug', $leagueSlug)->first();

        if (!$league) {
            $this->error("  ❌ League '{$leagueSlug}' not found. Please seed it first.");
            $this->errorCount++;
            return;
        }

        // Get standings from NCAA API
        $sportApi = "basketball-{$gender}";
        $standings = $this->ncaaService->getStandings($sportApi, $division);

        if (!$standings || !isset($standings['data'])) {
            $this->error("  ❌ Failed to fetch standings for {$sportApi} {$division}");
            $this->errorCount++;
            return;
        }

        $teamCount = 0;
        $bar = $this->output->createProgressBar();
        $bar->start();

        // Parse standings data
        foreach ($standings['data'] as $conference) {
            if (!isset($conference['standings'])) {
                continue;
            }

            foreach ($conference['standings'] as $teamData) {
                $bar->advance();
                
                try {
                    $this->importTeam($teamData, $league, $conference['conference'] ?? null);
                    $teamCount++;
                } catch (\Exception $e) {
                    $this->errorCount++;
                    \Log::error('Failed to import team: ' . $e->getMessage(), [
                        'team_data' => $teamData,
                        'league' => $league->slug,
                    ]);
                    // Continue with next team
                }
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("  ✓ Processed {$teamCount} teams");
        $this->newLine();
    }

    protected function importTeam(array $teamData, League $league, ?string $conference)
    {
        // Get team name from standings
        $teamName = $teamData['School'] ?? $teamData['Team'] ?? null;
        
        if (!$teamName) {
            return;
        }

        // Create slug from team name (make unique per league)
        $baseSlug = Str::slug($teamName);
        $slug = $league->slug . '-' . $baseSlug;
        
        // Check if team exists
        $team = Team::where('league_id', $league->id)
            ->where('slug', $slug)
            ->first();

        $teamInfo = [
            'sport_id' => $league->sport_id,
            'league_id' => $league->id,
            'name' => $teamName,
            'slug' => $slug,
            'code' => $this->extractTeamCode($teamName),
            'country' => 'USA',
            'is_active' => true,
            'api_id' => $league->slug . '-' . $slug, // Make unique per league
            'extra_data' => [
                'conference' => $conference,
                'standings_data' => $teamData,
            ],
            'synced_at' => now(),
        ];

        if ($team) {
            $team->update($teamInfo);
            $this->updatedCount++;
        } else {
            Team::create($teamInfo);
            $this->importedCount++;
        }
    }

    protected function extractTeamCode(string $teamName): string
    {
        // Extract initials from team name
        $words = explode(' ', $teamName);
        
        if (count($words) === 1) {
            return strtoupper(substr($teamName, 0, 3));
        }
        
        // Take first letter of each word, max 4 letters
        $code = '';
        foreach ($words as $word) {
            if (strlen($code) >= 4) break;
            if (strlen($word) > 0) {
                $code .= strtoupper($word[0]);
            }
        }
        
        return $code ?: strtoupper(substr($teamName, 0, 3));
    }
}
