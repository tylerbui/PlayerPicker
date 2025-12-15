<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Services\NewsService;
use Illuminate\Console\Command;

class SyncBasketballPlayerNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'player:sync-news-all {--sport=basketball : Sport slug to filter (e.g. basketball, nba)} {--limit=100 : Max players to update} {--per=5 : Articles per player} {--dry-run : Show what would be updated without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and store latest news articles for all basketball players';

    /**
     * Execute the console command.
     */
    public function handle(NewsService $news)
    {
        $limit = (int) $this->option('limit');
        $per = (int) $this->option('per');
        $dryRun = (bool) $this->option('dry-run');
        $sport = (string) $this->option('sport');

        $this->info("Syncing news for players in sport='{$sport}' (limit={$limit}, per={$per})...");

        $aliases = [$sport];
        if ($sport === 'basketball') {
            $aliases[] = 'nba';
        }

        $count = 0;
        Player::whereHas('team.sport', function ($q) use ($aliases) {
                $q->whereIn('slug', $aliases);
            })
            ->orderBy('last_name')
            ->chunk(50, function ($players) use ($news, $per, $dryRun, &$count, $limit) {
                foreach ($players as $player) {
                    if ($count >= $limit) {
                        return false; // stop chunking
                    }

                    $name = $player->full_name;
                    $this->line("→ {$name}");

                    $articles = $news->getPlayerNews($name, $per);
                    if (!$dryRun && $articles) {
                        $player->news = $articles;
                        $player->save();
                    }

                    $count++;
                    // Be polite to NewsAPI rate limits
                    usleep(250000); // 0.25s between requests
                }
            });

        $this->info("Done. Updated {$count} players.");
        return 0;
    }
}
