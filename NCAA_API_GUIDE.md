# NCAA API Integration Guide

## Overview
PlayerPicker now integrates with the NCAA API to fetch college sports data including live scores, stats, rankings, standings, schedules, and game details.

**API Source**: https://github.com/henrygd/ncaa-api  
**Public API**: https://ncaa-api.henrygd.me  
**Rate Limit**: 5 requests per second per IP

## Setup Complete ✅

### 1. Configuration
- ✅ API base URL configured in `.env` as `NCAA_API_BASE_URL`
- ✅ Base URL configured in `config/services.php`
- ✅ Service class created at `app/Services/NcaaApiService.php`

### 2. Features Implemented
- **Caching**: API responses are cached (5 min for live data, 15 min for most data, 1 hour for static data)
- **Error Handling**: Logs errors and handles failed requests gracefully
- **Helper Methods**: Quick access to common queries (current scores, rankings, etc.)

## Usage

### Using the API Service in Code

```php
use App\Services\NcaaApiService;

// In a controller or service
public function example(NcaaApiService $ncaaService)
{
    // Get live football scores
    $scores = $ncaaService->getCurrentFootballScores('fbs');
    
    // Get men's basketball scores
    $basketballScores = $ncaaService->getCurrentMensBasketballScores();
    
    // Get football rankings (AP Top 25)
    $top25 = $ncaaService->getFootballTop25();
    
    // Get standings for a sport
    $standings = $ncaaService->getStandings('basketball-men', 'd1');
    
    // Get specific game details
    $gameDetails = $ncaaService->getGameDetails(6154104);
    
    // Get team schedule
    $schedule = $ncaaService->getTeamSchedule('basketball-men', 'd1', 'duke', 2024);
}
```

## API Methods

### Scoreboard
```php
getScoreboard(string $sport, string $division, int $season, int|string $week, string $conference)
```
Fetches live scores for a given sport, division, and date.

**Parameters:**
- `$sport`: 'football', 'basketball-men', 'basketball-women', etc.
- `$division`: 'fbs', 'fcs', 'd1', 'd2', 'd3'
- `$season`: Year (e.g., 2024)
- `$week`: Week number or 'all'
- `$conference`: Conference slug or 'all-conf'

**Example:**
```php
$scores = $ncaaService->getScoreboard('football', 'fbs', 2024, 13, 'big-ten');
```

### Stats
```php
getStats(string $sport, string $division, string $period, string $category, int $statId)
```
Get statistics for teams or individuals.

**Parameters:**
- `$sport`: 'football', 'basketball-men', etc.
- `$division`: 'fbs', 'd1', etc.
- `$period`: 'current', '2023', '2024', etc.
- `$category`: 'team' or 'individual'
- `$statId`: Stat category ID (28 = scoring defense, etc.)

**Example:**
```php
$scoringDefense = $ncaaService->getStats('football', 'fbs', 'current', 'team', 28);
```

### Rankings
```php
getRankings(string $sport, string $division, string $season, int|string $week)
```
Get AP Poll rankings or other polls.

**Parameters:**
- `$sport`: 'football', 'basketball-men', 'basketball-women'
- `$division`: 'fbs', 'd1'
- `$season`: Year as string (e.g., '2024')
- `$week`: Week number or 'final' or 'current'

**Example:**
```php
$rankings = $ncaaService->getRankings('football', 'fbs', '2024', 13);
```

### Standings
```php
getStandings(string $sport, string $division)
```
Get conference standings.

**Parameters:**
- `$sport`: 'football', 'basketball-men', 'basketball-women'
- `$division`: 'fbs', 'fcs', 'd1', 'd2', 'd3'

**Example:**
```php
$standings = $ncaaService->getStandings('basketball-men', 'd1');
```

### Team Schedule
```php
getTeamSchedule(string $sport, string $division, string $teamSeo, int $season)
```
Get a team's schedule for a season.

**Parameters:**
- `$sport`: Sport name
- `$division`: Division
- `$teamSeo`: Team's SEO slug (e.g., 'duke', 'michigan', 'north-carolina')
- `$season`: Year

**Example:**
```php
$schedule = $ncaaService->getTeamSchedule('basketball-men', 'd1', 'duke', 2024);
```

### Game Details
```php
getGameDetails(int $gameId)
```
Get detailed information about a specific game including box score, play-by-play, scoring summary, and team stats.

**Example:**
```php
$game = $ncaaService->getGameDetails(6154104);
```

### Conference Teams
```php
getConferenceTeams(string $sport, string $division, string $conference)
```
Get all teams in a conference.

**Example:**
```php
$accTeams = $ncaaService->getConferenceTeams('basketball-men', 'd1', 'acc');
```

## Helper Methods

These are convenience methods for common queries:

```php
// Current football scores (defaults to FBS)
$scores = $ncaaService->getCurrentFootballScores('fbs');

// Current men's basketball scores
$scores = $ncaaService->getCurrentMensBasketballScores();

// Current women's basketball scores
$scores = $ncaaService->getCurrentWomensBasketballScores();

// Football AP Top 25 (defaults to current season)
$top25 = $ncaaService->getFootballTop25();
$top25Week5 = $ncaaService->getFootballTop25(2024, 5);

// Men's basketball AP Poll
$poll = $ncaaService->getMensBasketballPoll();
```

## Common Sports & Divisions

### Sports
- **football** - College Football
- **basketball-men** - Men's Basketball
- **basketball-women** - Women's Basketball
- **baseball** - Baseball
- **softball** - Softball
- **lacrosse-men** - Men's Lacrosse
- **lacrosse-women** - Women's Lacrosse
- **hockey-men** - Men's Ice Hockey
- **soccer-men** - Men's Soccer
- **soccer-women** - Women's Soccer
- **volleyball-women** - Women's Volleyball

### Football Divisions
- **fbs** - Football Bowl Subdivision (D1)
- **fcs** - Football Championship Subdivision (D1)
- **d2** - Division II
- **d3** - Division III

### Basketball/Other Sports Divisions
- **d1** - Division I
- **d2** - Division II
- **d3** - Division III

## Common Conferences

### Power 5 Conferences
- **acc** - Atlantic Coast Conference
- **big-ten** - Big Ten Conference
- **big-12** - Big 12 Conference
- **sec** - Southeastern Conference
- **pac-12** - Pacific-12 Conference (historical)

### Group of 5 Conferences
- **american** - American Athletic Conference
- **mwc** - Mountain West Conference
- **mac** - Mid-American Conference
- **cusa** - Conference USA
- **sun-belt** - Sun Belt Conference

## API Response Examples

### Scoreboard Response
```json
{
  "games": [
    {
      "game": {
        "away": {
          "score": "24",
          "names": {
            "short": "Ohio St.",
            "seo": "ohio-st"
          },
          "rank": "2",
          "description": "(11-1)"
        },
        "home": {
          "score": "30",
          "names": {
            "short": "Michigan",
            "seo": "michigan"
          },
          "rank": "3",
          "description": "(12-0)"
        },
        "gameID": "3146430",
        "finalMessage": "FINAL",
        "startTime": "12:00PM ET",
        "gameState": "final"
      }
    }
  ]
}
```

### Rankings Response
```json
{
  "sport": "Football",
  "polls": [
    {
      "pollName": "AP Top 25",
      "ranks": [
        {
          "current": "1",
          "team": "Georgia",
          "points": "1550",
          "record": "15-0"
        }
      ]
    }
  ]
}
```

### Standings Response
```json
{
  "sport": "Women's Basketball",
  "title": "ALL CONFERENCES",
  "data": [
    {
      "conference": "ASUN",
      "standings": [
        {
          "School": "FGCU",
          "Conference W": "9",
          "Conference L": "0",
          "Overall W": "19",
          "Overall L": "4",
          "Overall PCT": "0.826"
        }
      ]
    }
  ]
}
```

## Best Practices

### 1. Cache Management
- **Live scores**: 5 minutes cache
- **Dynamic data** (fixtures, schedules): 15 minutes cache
- **Static data** (standings, rankings): 1 hour cache

```php
// Clear all cache if needed
$ncaaService->clearCache();
```

### 2. Rate Limiting
The public API is limited to **5 requests per second per IP**. Be mindful:
- Cache aggressively
- Batch operations when possible
- Consider hosting your own instance for heavy usage

### 3. Error Handling
Always check for null responses:
```php
$scores = $ncaaService->getCurrentFootballScores();

if (!$scores || empty($scores)) {
    Log::error("Failed to fetch NCAA scores");
    return response()->json(['error' => 'Unable to fetch scores'], 500);
}
```

## Next Steps

### 1. Create Controllers
```bash
php artisan make:controller Api/NcaaController
```

Example controller:
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NcaaApiService;
use Illuminate\Http\Request;

class NcaaController extends Controller
{
    public function __construct(
        protected NcaaApiService $ncaaService
    ) {}

    public function footballScores()
    {
        $scores = $this->ncaaService->getCurrentFootballScores();
        return response()->json($scores);
    }

    public function basketballScores()
    {
        $scores = $this->ncaaService->getCurrentMensBasketballScores();
        return response()->json($scores);
    }

    public function top25()
    {
        $rankings = $this->ncaaService->getFootballTop25();
        return response()->json($rankings);
    }
}
```

### 2. Add API Routes
```php
// routes/api.php
use App\Http\Controllers\Api\NcaaController;

Route::prefix('ncaa')->group(function () {
    Route::get('/football/scores', [NcaaController::class, 'footballScores']);
    Route::get('/basketball/scores', [NcaaController::class, 'basketballScores']);
    Route::get('/football/top25', [NcaaController::class, 'top25']);
    Route::get('/standings/{sport}/{division}', [NcaaController::class, 'standings']);
    Route::get('/game/{gameId}', [NcaaController::class, 'gameDetails']);
});
```

### 3. Frontend Integration
Example Vue component:
```vue
<script setup>
import { ref, onMounted } from 'vue'

const scores = ref([])

const fetchScores = async () => {
  const response = await fetch('/api/ncaa/football/scores')
  scores.value = await response.json()
}

onMounted(fetchScores)
</script>

<template>
  <div v-for="game in scores.games" :key="game.game.gameID">
    <div>{{ game.game.away.names.short }} {{ game.game.away.score }}</div>
    <div>{{ game.game.home.names.short }} {{ game.game.home.score }}</div>
    <div>{{ game.game.gameState }}</div>
  </div>
</template>
```

### 4. Self-Hosting (Optional)
If you need higher rate limits or reliability:

```bash
# Clone the NCAA API repository
git clone https://github.com/henrygd/ncaa-api.git

# Follow deployment instructions
# Update NCAA_API_BASE_URL in your .env
```

## Troubleshooting

### "Connection failed" or timeout errors
- The public API may be experiencing high traffic
- Consider implementing retry logic
- Consider self-hosting for production

### Rate limit exceeded
- You're making more than 5 requests per second
- Implement request queuing
- Increase cache durations
- Consider self-hosting

### Missing or null data
- The sport/division/team combination may not exist
- Check the NCAA.com website for valid paths
- Verify team SEO slugs are correct

## Resources
- **NCAA API GitHub**: https://github.com/henrygd/ncaa-api
- **Public API Demo**: https://ncaa-api.henrygd.me/openapi
- **NCAA Official Site**: https://www.ncaa.com

---

**Integration Status**: ✅ Complete and Ready to Use
