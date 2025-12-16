# NCAA Players Import Guide

## ✅ Players Imported Successfully

### Current Status
- **Total NCAA Players**: 413
- **Teams with rosters**: 27 (Men's D1 teams with ESPN IDs)
- **Average roster size**: ~15 players per team

## Import Command

### Command Syntax
```bash
php artisan ncaa:import-players {gender} {--limit=} {--team=}
```

### Parameters
- **gender**: `men`, `women`, or `all` (default: `all`)
- **--limit**: Limit number of teams to process (optional)
- **--team**: Specific team slug to import (optional)

### Examples

```bash
# Import players for all men's teams
php artisan ncaa:import-players men

# Import players for all women's teams
php artisan ncaa:import-players women

# Import all players (men's and women's)
php artisan ncaa:import-players all

# Test with first 5 teams
php artisan ncaa:import-players men --limit=5

# Import specific team
php artisan ncaa:import-players --team=ncaa-mens-d1-duke
```

## What Gets Imported

For each player, the following data is stored:

### Player Fields
- **first_name**: Player's first name
- **last_name**: Player's last name
- **slug**: URL-friendly identifier
- **position**: Position (G, F, C, etc.)
- **number**: Jersey number
- **height**: Height in total inches
- **weight**: Weight in pounds
- **photo**: ESPN headshot URL
- **espn_athlete_id**: ESPN athlete ID
- **api_id**: Prefixed with 'espn-'
- **team_id**: Reference to team
- **extra_data**: JSON containing additional ESPN data

### Sample Data
```json
{
  "first_name": "Cooper",
  "last_name": "Flagg",
  "position": "F",
  "number": 2,
  "height": 81,
  "weight": 205,
  "photo": "https://a.espncdn.com/i/headshots/mens-college-basketball/players/full/5105776.png",
  "espn_athlete_id": "5105776",
  "extra_data": {
    "experience": "Freshman"
  }
}
```

## Data Source

### ESPN Roster API
Players are imported from ESPN's college basketball roster API:
```
https://site.api.espn.com/apis/site/v2/sports/basketball/mens-college-basketball/teams/{espn_team_id}/roster
```

### Requirements
- ✅ Team must have `espn_team_id` populated
- ✅ ESPN must have roster data for that team

### Limitations
**Only 27 teams have ESPN IDs** (3.7% of all teams):
- These are typically major D1 programs
- 337 men's teams don't have ESPN IDs
- 362 women's teams don't have ESPN IDs

## Current Coverage

### Teams with Players
| Category | Count |
|----------|-------|
| Teams with ESPN IDs | 27 |
| Teams with rosters imported | 27 |
| Players imported | 413 |
| Teams without ESPN IDs | 699 |

### Popular Teams with Rosters
- Duke
- North Carolina
- Kansas
- Kentucky
- UConn
- Louisville
- Notre Dame
- Colorado
- And ~19 more major programs

## Viewing Imported Players

### Via Tinker
```php
// Count players by team
$duke = Team::where('slug', 'like', '%duke%')->first();
$players = $duke->players()->count();

// Get players with stats
$players = Player::with('team')
    ->whereHas('team.league', fn($q) => $q->where('api_type', 'ncaa'))
    ->get();

// Find players by position
$guards = Player::where('position', 'G')
    ->whereHas('team.league', fn($q) => $q->where('api_type', 'ncaa'))
    ->get();
```

### Via Database
```sql
-- Count players per team
SELECT t.name, COUNT(p.id) as player_count
FROM players p
JOIN teams t ON p.team_id = t.id
JOIN leagues l ON t.league_id = l.id
WHERE l.api_type = 'ncaa'
GROUP BY t.id
ORDER BY player_count DESC;

-- View sample players
SELECT 
    CONCAT(p.first_name, ' ', p.last_name) as name,
    p.position,
    p.number,
    t.name as team
FROM players p
JOIN teams t ON p.team_id = t.id
JOIN leagues l ON t.league_id = l.id
WHERE l.api_type = 'ncaa'
LIMIT 10;
```

## Re-importing / Updating Players

Running the import again will **update** existing players:

```bash
# This will update existing players with latest roster data
php artisan ncaa:import-players all
```

The command matches players by `team_id` and `espn_athlete_id`.

## Frontend Usage

### Display Team Roster
```php
// routes/web.php
Route::get('/teams/{team:slug}/roster', function (Team $team) {
    $players = $team->players()
        ->orderBy('number')
        ->get();
    
    return Inertia::render('teams/Roster', [
        'team' => $team,
        'players' => $players,
    ]);
});
```

### Show Player Profile
```php
Route::get('/players/{player:slug}', function (Player $player) {
    $player->load('team.league');
    
    return Inertia::render('players/Profile', [
        'player' => $player,
    ]);
});
```

### Filter by Position
```php
Route::get('/teams/{team:slug}/roster', function (Team $team, Request $request) {
    $query = $team->players();
    
    if ($position = $request->get('position')) {
        $query->where('position', $position);
    }
    
    $players = $query->orderBy('number')->get();
    
    return Inertia::render('teams/Roster', [
        'team' => $team,
        'players' => $players,
    ]);
});
```

## Player Data Fields Explained

### Height Format
Stored as total inches, can be converted to feet/inches:
```php
// In Player model or accessor
public function getFormattedHeightAttribute()
{
    if (!$this->height) return null;
    
    $feet = floor($this->height / 12);
    $inches = $this->height % 12;
    
    return "{$feet}' {$inches}\"";
}
```

### Photo URLs
Direct links to ESPN headshots:
```
https://a.espncdn.com/i/headshots/mens-college-basketball/players/full/{athlete_id}.png
```

Example: https://a.espncdn.com/i/headshots/mens-college-basketball/players/full/5105776.png

### Experience Levels
Stored in `extra_data->experience`:
- Freshman
- Sophomore
- Junior
- Senior
- Graduate

## Expanding Coverage

### Option 1: Fetch More ESPN IDs
Run the logo fetch command to get more ESPN team IDs:
```bash
# This will match more teams and populate espn_team_id
php artisan ncaa:fetch-logos all
```

Then import players again:
```bash
php artisan ncaa:import-players all
```

### Option 2: Manual ESPN ID Addition
For important teams, manually find and add ESPN IDs:

```php
$team = Team::where('name', 'Villanova')->first();
$team->espn_team_id = '222';
$team->save();

// Then import
php artisan ncaa:import-players --team=ncaa-mens-d1-villanova
```

### Option 3: Use Different Data Source
- NCAA.com (requires scraping)
- Sports Reference (requires scraping)
- Paid API services (SportsData.io, RapidAPI)

## Known Limitations

### 1. Limited Team Coverage
- Only 27/726 teams have rosters (3.7%)
- Women's teams have 0 rosters currently

### 2. ESPN API Constraints
- Only returns teams with active rosters
- Not all NCAA schools are on ESPN
- Smaller conferences may not be included

### 3. Women's Basketball
Currently no women's teams have ESPN IDs. To fix:
1. Run women's logo fetch with better matching
2. Or manually add ESPN team IDs for women's teams

### 4. Missing Data
Some players may be missing:
- Birth date/place
- Biography
- Stats (would need separate import)

## Next Steps

### 1. Import Player Stats
ESPN provides detailed stats. Create command to fetch:
```bash
php artisan ncaa:import-player-stats
```

### 2. Add More Teams
Find ESPN IDs for more teams:
- Check ESPN's college basketball page
- Match team names carefully
- Update `espn_team_id` field

### 3. Women's Rosters
Focus on getting ESPN IDs for women's teams:
```bash
# After adding ESPN IDs for women's teams
php artisan ncaa:import-players women
```

### 4. Roster Updates
Schedule automatic roster updates:
```php
// In app/Console/Kernel.php
$schedule->command('ncaa:import-players all')->weekly();
```

## Troubleshooting

### No Players Imported
Check if teams have ESPN IDs:
```php
$teamsWithIds = Team::whereNotNull('espn_team_id')
    ->whereHas('league', fn($q) => $q->where('api_type', 'ncaa'))
    ->count();
```

### "api_id field required" Error
This was fixed in the command. If you see it, make sure you're using the latest version of `ImportNcaaPlayers.php`.

### ESPN API Errors
Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

### Duplicate Players
Players are matched by `team_id` + `espn_athlete_id`. Running import again updates, doesn't duplicate.

## Command Output Example

```
🏀 Starting NCAA players import from ESPN...

📥 Importing Men's Basketball players...
  Found 27 teams with ESPN IDs
  337 teams don't have ESPN IDs (will be skipped)

 27/27 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

📥 Importing Women's Basketball players...
  Found 0 teams with ESPN IDs
  362 teams don't have ESPN IDs (will be skipped)

✅ Player import completed!
+----------------------------+-------+
| Status                     | Count |
+----------------------------+-------+
| Players imported (new)     | 413   |
| Players updated            | 0     |
| Teams processed            | 27    |
| Teams skipped (no ESPN ID) | 0     |
| Errors                     | 0     |
+----------------------------+-------+
```

---

**Current Status**: ✅ **413 NCAA D1 Basketball Players Imported Successfully!**  
**Coverage**: 27/726 teams (3.7%)  
**Recommendation**: Add more ESPN team IDs to expand coverage
