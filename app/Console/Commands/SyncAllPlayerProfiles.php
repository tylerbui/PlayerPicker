<?php

namespace App\Console\Commands;

use App\Models\Player;
use Illuminate\Console\Command;

class SyncAllPlayerProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'player:sync-all-profiles {--season=2024} {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync profiles for all players in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $season = $this->option('season');
        $limit = $this->option('limit');

        $query = Player::query();
        
        if ($limit) {
            $query->limit((int) $limit);
        }
        
        $players = $query->get();
        $total = $players->count();

        if ($total === 0) {
            $this->info('No players found.');
            return 0;
        }

        $this->info("Syncing profiles for {$total} players...");
        $bar = $this->output->createProgressBar($total);

        $success = 0;
        $failed = 0;

        foreach ($players as $player) {
            $exitCode = $this->call('player:sync-profile', [
                'player_id' => $player->id,
                '--season' => $season,
            ]);

            if ($exitCode === 0) {
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
            
            // Rate limiting: sleep for 1 second between requests
            sleep(1);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Sync complete: {$success} successful, {$failed} failed");

        return 0;
    }
}
