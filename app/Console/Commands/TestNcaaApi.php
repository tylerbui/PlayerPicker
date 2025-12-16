<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NcaaApiService;

class TestNcaaApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ncaa:test {type=all : Type of test (all, football, basketball, rankings)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NCAA API integration';

    /**
     * Execute the console command.
     */
    public function handle(NcaaApiService $ncaaService)
    {
        $type = $this->argument('type');

        $this->info('Testing NCAA API Integration...');
        $this->newLine();

        if ($type === 'all' || $type === 'football') {
            $this->testFootball($ncaaService);
        }

        if ($type === 'all' || $type === 'basketball') {
            $this->testBasketball($ncaaService);
        }

        if ($type === 'all' || $type === 'rankings') {
            $this->testRankings($ncaaService);
        }

        $this->newLine();
        $this->info('NCAA API tests completed!');
        return Command::SUCCESS;
    }

    protected function testFootball(NcaaApiService $ncaaService)
    {
        $this->comment('Testing Football Scores...');
        $scores = $ncaaService->getCurrentFootballScores('fbs');

        if (!$scores || !isset($scores['games'])) {
            $this->error('Failed to fetch football scores');
            return;
        }

        $gameCount = count($scores['games']);
        $this->info("Found {$gameCount} football games");

        if ($gameCount > 0) {
            $game = $scores['games'][0]['game'];
            $this->line("Sample game: {$game['away']['names']['short']} vs {$game['home']['names']['short']}");
        }
        $this->newLine();
    }

    protected function testBasketball(NcaaApiService $ncaaService)
    {
        $this->comment('Testing Men\'s Basketball Scores...');
        $scores = $ncaaService->getCurrentMensBasketballScores();

        if (!$scores || !isset($scores['games'])) {
            $this->error('Failed to fetch basketball scores');
            return;
        }

        $gameCount = count($scores['games']);
        $this->info("Found {$gameCount} basketball games");

        if ($gameCount > 0) {
            $game = $scores['games'][0]['game'];
            $this->line("Sample game: {$game['away']['names']['short']} vs {$game['home']['names']['short']}");
        }
        $this->newLine();
    }

    protected function testRankings(NcaaApiService $ncaaService)
    {
        $this->comment('Testing Football Rankings...');
        $rankings = $ncaaService->getFootballTop25();

        if (!$rankings) {
            $this->error('Failed to fetch rankings');
            return;
        }

        $this->info('Successfully fetched AP Top 25 rankings');
        if (isset($rankings['polls'][0]['ranks'])) {
            $topTeam = $rankings['polls'][0]['ranks'][0];
            $this->line("#1 Team: {$topTeam['team']} ({$topTeam['record']})");
        }
        $this->newLine();
    }
}
