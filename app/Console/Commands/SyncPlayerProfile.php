<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Services\ApiSportsService;
use App\Services\NewsService;
use App\Services\SportsDbService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPlayerProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'player:sync-profile {player_id} {--season=2024}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync player profile data including stats, recent games, and biography';

    /**
     * Execute the console command.
     */
public function handle(ApiSportsService $apiService, NewsService $newsService, SportsDbService $sportsDb)
    {
        $playerId = $this->argument('player_id');
        $season = $this->option('season');

        $player = Player::find($playerId);

        if (!$player) {
            $this->error("Player with ID {$playerId} not found.");
            return 1;
        }

        $this->info("Syncing profile for {$player->full_name}...");

        // Determine sport from team
        $sport = $player->team->sport->slug ?? 'basketball';
        $apiService->setSport($sport);

        try {
            // Fetch current season stats
            $this->info('Fetching current season stats...');
            $currentSeasonData = $apiService->getPlayerSeasonStats($player->api_id, $season);
            
            // Debug: Show structure
            if ($currentSeasonData) {
                $this->info('DEBUG: Response keys: ' . implode(', ', array_keys($currentSeasonData)));
            }
            
            if ($currentSeasonData) {
                $player->current_season_stats = $currentSeasonData['statistics'] ?? [];
                $this->info('✓ Current season stats updated');
            }

            // Fetch previous season stats
            $previousSeason = $season - 1;
            $this->info("Fetching {$previousSeason} season stats...");
            $previousSeasonData = $apiService->getPlayerSeasonStats($player->api_id, $previousSeason);
            
            if ($previousSeasonData) {
                $player->previous_season_stats = $previousSeasonData['statistics'] ?? [];
                $this->info('✓ Previous season stats updated');
            }

            // Fetch recent games
            $this->info('Fetching recent games...');
            $recentGames = $apiService->getPlayerRecentGames($player->api_id, $season, 10);
            
            if ($recentGames) {
                $player->recent_games_stats = $recentGames;
                $this->info('✓ Recent games updated');
            }

            // Update biography and photo from API data if available
            if ($currentSeasonData && isset($currentSeasonData['player'])) {
                $apiPlayer = $currentSeasonData['player'];
                
                // Debug: Log what photo data we're getting
                $this->info('DEBUG: Photo in API data: ' . ($apiPlayer['photo'] ?? 'NULL'));
                $this->info('DEBUG: Current player photo: ' . ($player->photo ?? 'NULL'));
                
                // Save photo from API-Sports if available
                if (empty($player->photo) && !empty($apiPlayer['photo'])) {
                    $player->photo = $apiPlayer['photo'];
                    $this->info('✓ Photo updated from API-Sports');
                }
                
                // Build a simple biography from available data
                $bio = [];
                if (isset($apiPlayer['firstname']) && isset($apiPlayer['lastname'])) {
                    $bio[] = "{$apiPlayer['firstname']} {$apiPlayer['lastname']}";
                }
                if (isset($apiPlayer['birth']['date']) && isset($apiPlayer['birth']['place'])) {
                    $bio[] = "Born {$apiPlayer['birth']['date']} in {$apiPlayer['birth']['place']}.";
                }
                if (isset($apiPlayer['nationality'])) {
                    $bio[] = "Nationality: {$apiPlayer['nationality']}.";
                }
                
                if (!empty($bio) && empty($player->biography)) {
                    $player->biography = implode(' ', $bio);
                    $this->info('✓ Biography updated');
                }
            }

            // Fallback enrichment via TheSportsDB
            if (empty($player->biography) || empty($player->photo)) {
                $this->info('Enriching from TheSportsDB...');
                $enriched = $sportsDb->enrichByName($player->full_name);
                if ($enriched) {
                    if (empty($player->biography) && !empty($enriched['strDescriptionEN'])) {
                        $player->biography = $enriched['strDescriptionEN'];
                        $this->info('✓ Biography enriched from SportsDB');
                    }
                    if (empty($player->photo)) {
                        $photo = $enriched['strCutout'] ?? $enriched['strThumb'] ?? null;
                        if ($photo) {
                            $player->photo = $photo;
                            $this->info('✓ Photo enriched from SportsDB');
                        }
                    }
                }
            }

            // Fetch news
            $this->info('Fetching latest news...');
            $news = $newsService->getPlayerNews($player->full_name, 5);
            
            if ($news) {
                $player->news = $news;
                $this->info('✓ News updated');
            }

            // Update sync timestamp
            $player->stats_synced_at = now();
            $player->save();

            $this->info("✅ Successfully synced profile for {$player->full_name}");
            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to sync player profile: {$e->getMessage()}");
            Log::error('Player profile sync failed', [
                'player_id' => $playerId,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }
}
