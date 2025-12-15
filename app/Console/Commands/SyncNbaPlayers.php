<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\Player;
use Illuminate\Support\Facades\Http;

class SyncNbaPlayers extends Command
{
    protected $signature = 'api:sync-nba-players {season=2024} {--team_id=}';
    protected $description = 'Sync NBA players for a season. Use --team_id to sync specific team';

    public function handle()
    {
        $season = $this->argument('season');
        $teamIdFilter = $this->option('team_id');

        // Get all NBA teams or specific team
        $query = Team::whereHas('sport', fn($q) => $q->where('name', 'Basketball'));
        
        if ($teamIdFilter) {
            $query->where('id', $teamIdFilter);
        }
        
        $teams = $query->get();

        if ($teams->isEmpty()) {
            $this->error('No NBA teams found. Run api:sync-nba-teams first!');
            return 1;
        }

        $this->info("Syncing players for {$teams->count()} team(s), season {$season}...");
        $this->newLine();

        $totalPlayers = 0;

        foreach ($teams as $team) {
            $this->info("📥 Fetching players for {$team->name}...");

            try {
                $response = Http::withHeaders([
                    'x-apisports-key' => config('services.api_sports.key'),
                ])->get('https://v2.nba.api-sports.io/players', [
                    'team' => $team->api_id,
                    'season' => $season,
                ]);

                if (!$response->successful()) {
                    $this->error("  ❌ Failed to fetch players for {$team->name}");
                    continue;
                }

                $data = $response->json();
                $players = $data['response'] ?? [];

                if (empty($players)) {
                    $this->warn("  ⚠️  No players found for {$team->name}");
                    continue;
                }

                $bar = $this->output->createProgressBar(count($players));
                $bar->start();

                $synced = 0;

                foreach ($players as $playerData) {
                    try {
                        Player::updateOrCreate(
                            ['api_id' => (string)$playerData['id']],
                            [
                                'team_id' => $team->id,
                                'first_name' => $playerData['firstname'] ?? '',
                                'last_name' => $playerData['lastname'] ?? '',
                                'slug' => \Str::slug(($playerData['firstname'] ?? '') . ' ' . ($playerData['lastname'] ?? '')),
                                'birth_date' => isset($playerData['birth']['date']) ? $playerData['birth']['date'] : null,
                                'birth_place' => $playerData['birth']['place'] ?? null,
                                'birth_country' => $playerData['birth']['country'] ?? null,
                                'nationality' => $playerData['nationality'] ?? null,
                                'height' => $this->parseHeight($playerData['height']['meters'] ?? null),
                                'weight' => $this->parseWeight($playerData['weight']['kilograms'] ?? null),
                                'position' => $playerData['leagues']['standard']['pos'] ?? null,
                                'number' => $playerData['leagues']['standard']['jersey'] ?? null,
                                'photo' => null, // NBA API v2 doesn't provide photos
                                'current_season_stats' => null, // Will be synced separately
                                'extra_data' => $playerData,
                                'synced_at' => now(),
                                'is_active' => $playerData['leagues']['standard']['active'] ?? true,
                            ]
                        );
                        $synced++;
                    } catch (\Exception $e) {
                        $this->error("\n  Error syncing player: " . $e->getMessage());
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
                $this->info("  ✅ Synced {$synced} players for {$team->name}");
                $totalPlayers += $synced;

            } catch (\Exception $e) {
                $this->error("  ❌ Error: " . $e->getMessage());
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("🎉 Total players synced: {$totalPlayers}");

        return 0;
    }

    private function parseHeight($meters): ?int
    {
        if (!$meters) return null;
        
        // Convert to cm
        return (int)($meters * 100);
    }

    private function parseWeight($kg): ?int
    {
        if (!$kg) return null;
        
        return (int)$kg;
    }
}
