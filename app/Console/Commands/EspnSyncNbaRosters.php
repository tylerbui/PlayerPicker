<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\Team;
use App\Services\EspnService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class EspnSyncNbaRosters extends Command
{
    protected $signature = 'espn:sync-nba-rosters {--team=}';
    protected $description = 'Sync NBA team rosters from ESPN and upsert players (preserve existing photos)';

    public function handle(EspnService $espn)
    {
        $teamFilter = $this->option('team'); // abbr or espn id

        $teamsQuery = Team::query();
        if ($teamFilter) {
            $teamsQuery->where(function ($q) use ($teamFilter) {
                $q->where('code', $teamFilter)->orWhere('espn_team_id', $teamFilter);
            });
        } else {
            $teamsQuery->whereNotNull('espn_team_id');
        }

        $teams = $teamsQuery->get();
        if ($teams->isEmpty()) {
            $this->error('No teams found with ESPN IDs. Run espn:sync-nba-teams first.');
            return 1;
        }

        $total = 0;
        foreach ($teams as $team) {
            $idOrAbbr = $team->espn_team_id ?: $team->code;
            $this->info("Fetching roster for {$team->name} ({$idOrAbbr})...");
            $athletes = $espn->getTeamRoster($idOrAbbr);
            if (empty($athletes)) { $this->warn('  No athletes returned'); continue; }

            $bar = $this->output->createProgressBar(count($athletes));
            $bar->start();

            foreach ($athletes as $ath) {
                $espnId = (string)($ath['id'] ?? '');
                $first = $ath['firstName'] ?? ($ath['name'] ?? '');
                $last = $ath['lastName'] ?? ($ath['lastName'] ?? '');
                $full = trim(($ath['fullName'] ?? "$first $last"));
                $jersey = $ath['jersey'] ?? null;
                $pos = data_get($ath, 'position.abbreviation') ?? data_get($ath, 'position.name');
                $headshot = data_get($ath, 'headshot.href');

                $player = Player::where('espn_athlete_id', $espnId)
                    ->orWhere(function ($q) use ($first, $last) {
                        $q->where('first_name', $first)->where('last_name', $last);
                    })->first();

                if (!$player) {
                    $player = new Player();
                    $player->api_id = 'espn-'.$espnId; // ensure uniqueness for required column
                }

                $player->espn_athlete_id = $espnId;
                $player->team_id = $team->id;
                $player->first_name = $first ?: ($player->first_name ?? '');
                $player->last_name = $last ?: ($player->last_name ?? '');
                $player->slug = $player->slug ?: Str::slug($full);
                $player->position = $pos ?: $player->position;
                $player->number = $jersey ?: $player->number;
                // Preserve existing photo if set; backfill from ESPN only if empty
                if (empty($player->photo) && $headshot) {
                    $player->photo = $headshot;
                }
                $player->extra_data = $ath;
                $player->synced_at = now();
                $player->is_active = true;
                $player->save();

                $bar->advance();
                $total++;
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info("Roster sync complete. Processed {$total} players.");
        return 0;
    }
}