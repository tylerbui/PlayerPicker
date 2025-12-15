# API-Sports.io Integration Guide

## Overview
PlayerPicker is now integrated with API-Sports.io to fetch real-time sports data including teams, players, leagues, and live game statistics.

## Setup Complete ✅

### 1. Configuration
- ✅ API key added to `.env` as `API_SPORTS_KEY`
- ✅ Base URL configured in `config/services.php`
- ✅ Service class created at `app/Services/ApiSportsService.php`

### 2. Features Implemented
- **Caching**: API responses are cached to reduce API calls (free plan = 100 requests/day)
- **Error Handling**: Logs errors and handles failed requests gracefully
- **Data Sync Commands**: Artisan commands to sync teams and players

## Usage

### Syncing Data from API

#### 1. Sync Teams
```bash
php artisan api:sync-teams {league_id} {season}

# Example: Sync Premier League teams for 2024
php artisan api:sync-teams 39 2024

# Example: Sync NBA teams for 2024
php artisan api:sync-teams 12 2024
```

#### 2. Sync Players
```bash
php artisan api:sync-players {team_api_id} {season}

# Example: Sync Manchester United players for 2024
php artisan api:sync-players 33 2024

# Example: Sync Lakers players for 2024
php artisan api:sync-players 145 2024
```

### Using the API Service in Code

```php
use App\Services\ApiSportsService;

// In a controller or service
public function example(ApiSportsService $apiService)
{
    // Search for players
    $players = $apiService->searchPlayers('Messi', leagueId: 39, season: 2024);
    
    // Get live games
    $liveGames = $apiService->getLiveGames();
    
    // Get fixtures by date
    $fixtures = $apiService->getFixturesByDate('2024-12-10', leagueId: 39);
    
    // Get player stats
    $stats = $apiService->getPlayerStats(playerId: 154, season: 2024);
    
    // Get team info
    $team = $apiService->getTeam(teamId: 33);
}
```

## Common League IDs

### Football (Soccer)
- **39** - Premier League (England)
- **140** - La Liga (Spain)
- **78** - Bundesliga (Germany)
- **135** - Serie A (Italy)
- **61** - Ligue 1 (France)

### Basketball
- **12** - NBA
- **117** - WNBA

### American Football
- **1** - NFL

### Finding More League IDs
Visit the API-Sports dashboard or use:
```php
$leagues = $apiService->getLeagues(country: 'England');
```

## API Response Structure

### Teams Response
```json
{
  "team": {
    "id": 33,
    "name": "Manchester United",
    "code": "MUN",
    "country": "England",
    "founded": 1878,
    "national": false,
    "logo": "https://media.api-sports.io/football/teams/33.png"
  },
  "venue": {
    "id": 556,
    "name": "Old Trafford",
    "city": "Manchester",
    "capacity": 76212,
    "image": "https://media.api-sports.io/football/venues/556.png"
  }
}
```

### Players Response
```json
{
  "player": {
    "id": 154,
    "name": "Cristiano Ronaldo",
    "firstname": "Cristiano",
    "lastname": "Ronaldo",
    "age": 38,
    "birth": {
      "date": "1985-02-05",
      "place": "Funchal",
      "country": "Portugal"
    },
    "nationality": "Portugal",
    "height": "187 cm",
    "weight": "83 kg",
    "photo": "https://media.api-sports.io/football/players/154.png"
  },
  "statistics": [
    {
      "games": {
        "position": "Attacker",
        "number": 7
      }
    }
  ]
}
```

## Database Tables

### Teams
Stores team information synced from API with fields:
- `api_id` - API-Sports team ID
- `name`, `code`, `country`, `city`
- `venue_*` - Venue information
- `logo` - Team logo URL
- `extra_data` - Full API response (JSON)
- `synced_at` - Last sync timestamp

### Players
Stores player information with fields:
- `api_id` - API-Sports player ID
- `team_id` - Reference to teams table
- `first_name`, `last_name`
- `birth_date`, `nationality`, `height`, `weight`
- `position`, `number` (jersey)
- `current_season_stats` - Latest stats (JSON)
- `photo` - Player photo URL
- `synced_at` - Last sync timestamp

## Best Practices

### 1. Cache Management
- **Static data** (teams, venues): 1 hour cache
- **Dynamic data** (fixtures): 15 minutes cache
- **Live data** (games, scores): No cache

```php
// Clear cache when needed
$apiService->clearCache();
```

### 2. Rate Limiting
Free plan = 100 API calls per day per sport. Be mindful:
- Sync during off-peak hours
- Cache aggressively
- Batch operations when possible

### 3. Error Handling
Always check for null responses:
```php
$teams = $apiService->getTeams($leagueId, $season);

if (empty($teams)) {
    // Handle error
    Log::error("Failed to fetch teams");
    return;
}
```

## Next Steps

### 1. Create Controllers
```bash
php artisan make:controller Api/TeamController
php artisan make:controller Api/PlayerController
```

### 2. Add API Routes
```php
// routes/api.php
Route::get('/teams/search', [TeamController::class, 'search']);
Route::get('/players/search', [PlayerController::class, 'search']);
Route::get('/games/live', [GameController::class, 'live']);
```

### 3. Schedule Automatic Syncing
Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    // Sync teams daily at 3 AM
    $schedule->command('api:sync-teams 39 2024')->dailyAt('03:00');
    
    // Sync players weekly
    $schedule->command('api:sync-players 33 2024')->weekly();
}
```

### 4. Create Frontend Search
Build a search interface that:
- Searches local database first (fast)
- Falls back to API if not found
- Caches results for future searches

## Troubleshooting

### "API request failed"
- Check your API key in `.env`
- Verify you haven't exceeded rate limit (100/day)
- Check API-Sports dashboard for account status

### "Team not found in database"
- Sync teams first: `php artisan api:sync-teams {league_id} {season}`
- Verify team API ID is correct

### Slow Performance
- Increase cache duration
- Use database queries instead of API calls
- Implement queue jobs for bulk syncing

## Support
- API-Sports Documentation: https://api-sports.io/documentation
- Dashboard: https://dashboard.api-sports.io

---

**Integration Status**: ✅ Complete and Ready to Use
