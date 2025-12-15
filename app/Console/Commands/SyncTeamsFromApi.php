<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiSportsService;
use App\Models\Team;
use App\Models\League;

class SyncTeamsFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:sync-teams {sport} {league_id} {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync teams from API-Sports by sport, league and season. Sport: football, nba, nfl, etc.';

    /**
     * Execute the console command.
     */
    public function handle(ApiSportsService $apiService)
    {
        $sport = $this->argument('sport');
        $leagueId = $this->argument('league_id');
        $season = $this->argument('season');

        // Set the sport for API calls
        $apiService->setSport($sport);

        $this->info("Fetching {$sport} teams for league {$leagueId}, season {$season}...");

        $teams = $apiService->getTeams($leagueId, $season);

        if (empty($teams)) {
            $this->error('No teams found or API request failed.');
            return 1;
        }

        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();

        $synced = 0;

        foreach ($teams as $teamData) {
            $team = $teamData['team'];
            $venue = $teamData['venue'] ?? [];

            Team::updateOrCreate(
                ['api_id' => $team['id']],
                [
                    'league_id' => $leagueId,
                    'name' => $team['name'],
                    'slug' => \Str::slug($team['name']),
                    'code' => $team['code'] ?? null,
                    'country' => $team['country'] ?? null,
                    'founded' => $team['founded'] ?? null,
                    'is_national' => $team['national'] ?? false,
                    'logo' => $team['logo'] ?? null,
                    'venue_name' => $venue['name'] ?? null,
                    'venue_address' => $venue['address'] ?? null,
                    'venue_city' => $venue['city'] ?? null,
                    'venue_capacity' => $venue['capacity'] ?? null,
                    'venue_surface' => $venue['surface'] ?? null,
                    'venue_image' => $venue['image'] ?? null,
                    'extra_data' => $teamData,
                    'synced_at' => now(),
                ]
            );

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully synced {$synced} teams!");

        return 0;
    }
}
