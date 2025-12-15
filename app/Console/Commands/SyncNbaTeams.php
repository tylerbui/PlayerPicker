<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiSportsService;
use App\Models\Team;
use App\Models\Sport;

class SyncNbaTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:sync-nba-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all NBA teams from API-Sports';

    /**
     * Execute the console command.
     */
    public function handle(ApiSportsService $apiService)
    {
        $this->info("Fetching NBA teams...");

        // Set sport to NBA
        $apiService->setSport('nba');

        // Get all NBA teams (no league/season params needed)
        $response = \Http::withHeaders([
            'x-apisports-key' => config('services.api_sports.key'),
        ])->get('https://v2.nba.api-sports.io/teams');

        if (!$response->successful()) {
            $this->error('API request failed!');
            return 1;
        }

        $data = $response->json();
        $teams = $data['response'] ?? [];

        if (empty($teams)) {
            $this->error('No teams found!');
            return 1;
        }

        // Get or create NBA sport
        $nbaSport = Sport::firstOrCreate(
            ['name' => 'Basketball'],
            [
                'slug' => 'basketball',
                'api_name' => 'nba',
                'type' => 'team',
                'is_active' => true,
            ]
        );

        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();

        $synced = 0;
        $nbaFranchiseCount = 0;

        foreach ($teams as $teamData) {
            // Only sync official NBA franchise teams
            if (!($teamData['nbaFranchise'] ?? false)) {
                $bar->advance();
                continue;
            }

            Team::updateOrCreate(
                ['api_id' => (string)$teamData['id']],
                [
                    'sport_id' => $nbaSport->id,
                    'name' => $teamData['name'] ?? $teamData['nickname'],
                    'slug' => \Str::slug($teamData['name'] ?? $teamData['nickname']),
                    'code' => $teamData['code'] ?? null,
                    'city' => $teamData['city'] ?? null,
                    'country' => 'USA', // NBA teams are US-based
                    'logo' => $teamData['logo'] ?? null,
                    'extra_data' => $teamData,
                    'synced_at' => now(),
                    'is_active' => true,
                ]
            );

            $synced++;
            $nbaFranchiseCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully synced {$nbaFranchiseCount} NBA teams (filtered from {$synced} total)!");

        return 0;
    }
}
