# NCAA Teams Import Guide

## ✅ Teams Imported Successfully

### Basketball D1
- **Men's D1**: 364 teams
- **Women's D1**: 362 teams
- **Total**: 726 teams

## Import Command

### Command Syntax
```bash
php artisan ncaa:import-teams {sport} {gender} {division}
```

### Parameters
- **sport**: `basketball` (currently only basketball supported)
- **gender**: `men`, `women`, or `all`
- **division**: `d1`, `d2`, `d3`, or `all`

### Examples

```bash
# Import Men's D1 basketball teams
php artisan ncaa:import-teams basketball men d1

# Import Women's D1 basketball teams
php artisan ncaa:import-teams basketball women d1

# Import all Men's basketball (D1, D2, D3)
php artisan ncaa:import-teams basketball men all

# Import ALL basketball teams (all genders and divisions)
php artisan ncaa:import-teams basketball all all

# Default (basketball, all genders, all divisions)
php artisan ncaa:import-teams
```

## What Gets Imported

For each team, the following data is stored:

### Team Fields
- **name**: Full team name (e.g., "Duke", "North Carolina")
- **slug**: URL-friendly identifier (e.g., "ncaa-mens-d1-duke")
- **code**: Team abbreviation (e.g., "DUK", "NC")
- **sport_id**: Reference to Basketball sport
- **league_id**: Reference to specific NCAA league (Men's D1, Women's D1, etc.)
- **country**: "USA"
- **api_id**: Unique API identifier per league
- **extra_data**: JSON containing:
  - `conference`: Team's conference (e.g., "ACC", "Big Ten")
  - `standings_data`: Current season standings record

### Sample Data
```json
{
  "name": "Duke",
  "slug": "ncaa-mens-d1-duke",
  "code": "DUK",
  "country": "USA",
  "extra_data": {
    "conference": "ACC",
    "standings_data": {
      "School": "Duke",
      "Conference W": "16",
      "Conference L": "2",
      "Overall W": "28",
      "Overall L": "6"
    }
  }
}
```

## Viewing Imported Teams

### Via Tinker
```bash
php artisan tinker
```

```php
// Count teams by league
$mensD1 = Team::whereHas('league', fn($q) => $q->where('slug', 'ncaa-mens-d1'))->count();
$womensD1 = Team::whereHas('league', fn($q) => $q->where('slug', 'ncaa-womens-d1'))->count();

// Get teams from specific conference
$accTeams = Team::whereHas('league', fn($q) => $q->where('slug', 'ncaa-mens-d1'))
    ->whereJsonContains('extra_data->conference', 'ACC')
    ->get();

// List all teams with their conferences
Team::with('league')->where('league_id', $leagueId)->get(['name', 'extra_data']);
```

### Via Database
```sql
-- Count teams by league
SELECT l.name, COUNT(t.id) as team_count
FROM teams t
JOIN leagues l ON t.league_id = l.id
WHERE l.api_type = 'ncaa'
GROUP BY l.id;

-- View sample teams
SELECT name, code, JSON_EXTRACT(extra_data, '$.conference') as conference
FROM teams
WHERE league_id = (SELECT id FROM leagues WHERE slug = 'ncaa-mens-d1')
LIMIT 10;
```

## Re-importing / Updating Teams

Running the import command again will **update** existing teams rather than creating duplicates:

```bash
# This will update existing teams with latest standings data
php artisan ncaa:import-teams basketball men d1
```

## Known Limitations

### D2 and D3 Not Available
The NCAA API currently does not provide standings data for D2 and D3 divisions. Only D1 teams can be imported.

When you try to import D2 or D3:
```bash
php artisan ncaa:import-teams basketball men d2
# Result: ❌ Failed to fetch standings for basketball-men d2
```

### Missing Team Logos
Teams are imported without logos. Logos would need to be:
- Manually added
- Scraped from NCAA.com
- Fetched from another API (ESPN, etc.)

To add logos later:
```php
$team = Team::where('slug', 'ncaa-mens-d1-duke')->first();
$team->logo = 'https://example.com/duke-logo.png';
$team->save();
```

## Team Slug Structure

Teams have league-prefixed slugs to ensure uniqueness:

| League | Team Name | Slug |
|--------|-----------|------|
| NCAA Men's D1 | Duke | `ncaa-mens-d1-duke` |
| NCAA Women's D1 | Duke | `ncaa-womens-d1-duke` |
| NCAA Men's D1 | North Carolina | `ncaa-mens-d1-north-carolina` |

This allows the same school to have separate entries for men's and women's teams.

## Frontend Usage

### Display Teams List
```php
// routes/web.php
Route::get('/leagues/{league:slug}/teams', function (League $league) {
    $teams = $league->teams()
        ->orderBy('name')
        ->get();
    
    return Inertia::render('teams/Index', [
        'teams' => $teams,
        'league' => $league,
    ]);
});
```

### Show Teams by Conference
```php
Route::get('/leagues/{league:slug}/conferences/{conference}', function (League $league, string $conference) {
    $teams = $league->teams()
        ->whereJsonContains('extra_data->conference', $conference)
        ->orderBy('name')
        ->get();
    
    return Inertia::render('teams/ByConference', [
        'teams' => $teams,
        'league' => $league,
        'conference' => $conference,
    ]);
});
```

## Next Steps

### 1. Add Team Logos
Consider using ESPN's API or scraping NCAA.com for team logos.

### 2. Import Team Stats
Use the NCAA API to fetch detailed team statistics:
```php
$ncaaService = app(NcaaApiService::class);
$stats = $ncaaService->getStats('basketball-men', 'd1', 'current', 'team', 28);
```

### 3. Import Players
Create a similar command to import players for each team.

### 4. Add Conference Pages
Create dedicated pages for each conference showing standings and teams.

### 5. Live Scores Integration
Display live game scores on team pages using the NCAA API.

## Troubleshooting

### "League not found" Error
Make sure you've seeded the NCAA leagues first:
```bash
php artisan db:seed --class=NcaaBasketballSeeder
```

### Duplicate Key Errors
If you see `Duplicate entry` errors, the team slug or api_id is conflicting. The command now prefixes slugs with league identifiers to prevent this.

### API Rate Limiting
The public NCAA API is limited to 5 requests/second. If importing many divisions:
- Import one at a time
- Wait between imports
- Consider self-hosting the NCAA API

### No Teams Imported
Check the Laravel log for errors:
```bash
tail -f storage/logs/laravel.log
```

## Command Output Example

```
🏀 Starting NCAA basketball teams import...
Gender: men, Division: d1

📥 Importing NCAA Men's Basketball (D1)...
  364 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓]
  ✓ Processed 364 teams


✅ Import completed!
📊 Results:
+--------------------+-------+
| Status             | Count |
+--------------------+-------+
| Imported (new)     | 364   |
| Updated (existing) | 0     |
| Errors             | 0     |
| Total              | 364   |
+--------------------+-------+
```

---

**Status**: ✅ **726 NCAA D1 Basketball Teams Imported Successfully!**
