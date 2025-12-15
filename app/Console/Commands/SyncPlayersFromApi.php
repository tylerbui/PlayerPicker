<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiSportsService;
use App\Models\Player;
use App\Models\Team;

class SyncPlayersFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:sync-players {sport} {team_id} {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync players from API-Sports by sport, team and season. Sport: football, nba, nfl, etc.';

    /**
     * Execute the console command.
     */
    public function handle(ApiSportsService $apiService)
    {
        $sport = $this->argument('sport');
        $teamId = $this->argument('team_id');
        $season = $this->argument('season');

        // Set the sport for API calls
        $apiService->setSport($sport);

        // Find team in our database by API ID
        $team = Team::where('api_id', $teamId)->first();

        if (!$team) {
            $this->error("Team with API ID {$teamId} not found in database. Sync teams first!");
            return 1;
        }

        $this->info("Fetching players for {$team->name}, season {$season}...");

        $players = $apiService->getPlayers($teamId, $season);

        if (empty($players)) {
            $this->error('No players found or API request failed.');
            return 1;
        }

        $bar = $this->output->createProgressBar(count($players));
        $bar->start();

        $synced = 0;

        foreach ($players as $playerData) {
            $player = $playerData['player'];
            $statistics = $playerData['statistics'][0] ?? [];

            Player::updateOrCreate(
                ['api_id' => $player['id']],
                [
                    'team_id' => $team->id,
                    'first_name' => $player['firstname'] ?? '',
                    'last_name' => $player['lastname'] ?? '',
                    'slug' => \Str::slug($player['name'] ?? "{$player['firstname']} {$player['lastname']}"),
                    'birth_date' => $player['birth']['date'] ?? null,
                    'birth_place' => $player['birth']['place'] ?? null,
                    'birth_country' => $player['birth']['country'] ?? null,
                    'nationality' => $player['nationality'] ?? null,
                    'height' => $this->parseHeight($player['height'] ?? null),
                    'weight' => $this->parseWeight($player['weight'] ?? null),
                    'position' => $statistics['games']['position'] ?? null,
                    'number' => $statistics['games']['number'] ?? null,
                    'photo' => $player['photo'] ?? null,
                    'current_season_stats' => $statistics,
                    'extra_data' => $playerData,
                    'stats_synced_at' => now(),
                    'synced_at' => now(),
                ]
            );

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully synced {$synced} players for {$team->name}!");

        return 0;
    }

    /**
     * Parse height string (e.g., "180 cm" or "5'11\"")
     */
    private function parseHeight(?string $height): ?int
    {
        if (!$height) return null;
        
        // Extract numbers from string
        preg_match('/\d+/', $height, $matches);
        return $matches[0] ?? null;
    }

    /**
     * Parse weight string (e.g., "75 kg" or "165 lbs")
     */
    private function parseWeight(?string $weight): ?int
    {
        if (!$weight) return null;
        
        // Extract numbers from string
        preg_match('/\d+/', $weight, $matches);
        return $matches[0] ?? null;
    }
}
