<?php

namespace App\Console\Commands;

use App\Models\Sport;
use App\Models\Team;
use App\Services\EspnService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EspnSyncNbaTeams extends Command
{
    protected $signature = 'espn:sync-nba-teams';
    protected $description = 'Sync NBA teams from ESPN (ids, names, abbreviations, logos)';

    public function handle(EspnService $espn)
    {
        $this->info('Fetching ESPN NBA teams...');
        $teams = $espn->getTeams();
        if (empty($teams)) {
            $this->error('No teams returned from ESPN');
            return 1;
        }

        $sport = Sport::firstOrCreate(
            ['name' => 'Basketball'],
            ['slug' => 'basketball', 'api_name' => 'nba', 'type' => 'team', 'is_active' => true]
        );

        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();
        $synced = 0;
        foreach ($teams as $t) {
            $espnId = (string)($t['id'] ?? '');
            $abbr = $t['abbreviation'] ?? null;
            $name = $t['displayName'] ?? $t['name'] ?? null;
            $logo = $t['logo'] ?? ($t['logos'][0]['href'] ?? null);
            if (!$name || !$abbr) { $bar->advance(); continue; }

            // Match by ESPN id or fallback to abbreviation or slug
            $slug = Str::slug($name);
            $team = Team::where('espn_team_id', $espnId)
                ->orWhere('code', $abbr)
                ->orWhere('slug', $slug)
                ->first();

            if (!$team) {
                $team = new Team();
                $team->sport_id = $sport->id;
                $team->api_id = $team->api_id ?? ('espn-'.$espnId);
            }

            $team->espn_team_id = $espnId;
            $team->name = $name;
            $team->slug = $team->slug ?: $slug;
            $team->code = $abbr;
            $team->logo = $logo ?: $team->logo; // do not null-out existing
            $team->extra_data = $t;
            $team->synced_at = now();
            $team->is_active = true;
            $team->save();

            $synced++; $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Synced {$synced} teams from ESPN");
        return 0;
    }
}