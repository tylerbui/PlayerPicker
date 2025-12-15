<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NewsService
{
    /**
     * Fetch news for a player using NewsAPI or similar service
     * 
     * Note: You'll need to sign up for a free API key at https://newsapi.org
     * and add NEWS_API_KEY to your .env file
     */
    public function getPlayerNews(string $playerName, int $limit = 5)
    {
        $apiKey = config('services.news_api.key');
        
        if (!$apiKey) {
            Log::warning('NEWS_API_KEY not configured');
            return $this->getMockNews($playerName, $limit);
        }

        $cacheKey = "player_news_" . md5($playerName) . "_{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($playerName, $apiKey, $limit) {
            try {
                $response = Http::get('https://newsapi.org/v2/everything', [
                    'q' => $playerName,
                    'language' => 'en',
                    'sortBy' => 'publishedAt',
                    'pageSize' => $limit,
                    'apiKey' => $apiKey,
                ]);

                if ($response->successful()) {
                    $articles = $response->json()['articles'] ?? [];
                    
                    return collect($articles)->map(function ($article) {
                        return [
                            'title' => $article['title'] ?? 'No Title',
                            'source' => $article['source']['name'] ?? 'Unknown',
                            'date' => $article['publishedAt'] ?? now()->toDateString(),
                            'excerpt' => $article['description'] ?? '',
                            'url' => $article['url'] ?? '#',
                        ];
                    })->toArray();
                }

                return $this->getMockNews($playerName, $limit);

            } catch (\Exception $e) {
                Log::error('Failed to fetch player news', [
                    'player' => $playerName,
                    'error' => $e->getMessage()
                ]);
                
                return $this->getMockNews($playerName, $limit);
            }
        });
    }

    /**
     * Return mock news data when API is not available
     */
    private function getMockNews(string $playerName, int $limit = 5): array
    {
        return [
            [
                'title' => "{$playerName} continues impressive season performance",
                'source' => 'Sports News',
                'date' => now()->subDays(2)->toDateString(),
                'excerpt' => 'Latest updates on player performance and team dynamics.',
                'url' => '#',
            ],
            [
                'title' => "Team announces {$playerName} contract extension",
                'source' => 'ESPN',
                'date' => now()->subDays(5)->toDateString(),
                'excerpt' => 'Breaking news about player contract negotiations.',
                'url' => '#',
            ],
            [
                'title' => "{$playerName} shines in recent game",
                'source' => 'Bleacher Report',
                'date' => now()->subDays(7)->toDateString(),
                'excerpt' => 'Game highlights and player statistics breakdown.',
                'url' => '#',
            ],
        ];
    }

    /**
     * Alternative: Use RSS feeds for sports news
     */
    public function getPlayerNewsFromRss(string $playerName, int $limit = 5)
    {
        // Implement RSS feed parsing if needed
        // Example feeds: ESPN, Bleacher Report, etc.
        return [];
    }
}
