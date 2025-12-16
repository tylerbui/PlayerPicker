# NCAA API Quick Start

## What You Have Now ✅

Your PlayerPicker app now has **full NCAA college sports integration** for:
- 🏈 Football (FBS, FCS, D2, D3)
- 🏀 Men's & Women's Basketball
- ⚾ Baseball, Softball
- 🥍 Lacrosse
- 🏒 Hockey
- ⚽ Soccer
- 🏐 Volleyball
- And more!

## Testing the Integration

Test that everything works:

```bash
# Test basketball scores
php artisan ncaa:test basketball

# Test football scores
php artisan ncaa:test football

# Test all endpoints
php artisan ncaa:test all
```

## Using in Your Code

### Simple Example - Get Basketball Scores

```php
use App\Services\NcaaApiService;

// In any controller
public function getScores(NcaaApiService $ncaa)
{
    $scores = $ncaa->getCurrentMensBasketballScores();
    
    return response()->json($scores);
}
```

### Get Football Scores

```php
$scores = $ncaa->getCurrentFootballScores('fbs'); // or 'fcs', 'd2', 'd3'
```

### Get Standings

```php
$standings = $ncaa->getStandings('basketball-men', 'd1');
```

### Get Team Schedule

```php
// Get Duke's basketball schedule
$schedule = $ncaa->getTeamSchedule('basketball-men', 'd1', 'duke', 2024);
```

### Get Game Details

```php
$game = $ncaa->getGameDetails(6154104);
```

## Common Use Cases

### 1. Show Live College Basketball Scores on Homepage

```php
Route::get('/api/college-basketball', function (NcaaApiService $ncaa) {
    $scores = $ncaa->getCurrentMensBasketballScores();
    return response()->json($scores);
});
```

### 2. Conference Standings Widget

```php
Route::get('/api/standings/{sport}/{division}', function (
    NcaaApiService $ncaa,
    string $sport,
    string $division
) {
    $standings = $ncaa->getStandings($sport, $division);
    return response()->json($standings);
});
```

### 3. Top 25 Rankings Display

```php
Route::get('/api/football/top25', function (NcaaApiService $ncaa) {
    $rankings = $ncaa->getFootballTop25();
    return response()->json($rankings);
});
```

## Sports Available

- `football` - College Football
- `basketball-men` - Men's Basketball  
- `basketball-women` - Women's Basketball
- `baseball` - Baseball
- `softball` - Softball
- `lacrosse-men` / `lacrosse-women` - Lacrosse
- `hockey-men` - Ice Hockey
- `soccer-men` / `soccer-women` - Soccer
- `volleyball-women` - Volleyball

## Divisions

- `fbs` - Football Bowl Subdivision
- `fcs` - Football Championship Subdivision
- `d1` - Division I
- `d2` - Division II
- `d3` - Division III

## Popular Conferences

- `acc` - ACC
- `big-ten` - Big Ten
- `big-12` - Big 12
- `sec` - SEC
- `pac-12` - Pac-12

## Next Steps

1. **Read the full guide**: See `NCAA_API_GUIDE.md` for complete documentation
2. **Create API routes**: Add routes in `routes/api.php` for your frontend
3. **Build UI components**: Create Vue components to display the data
4. **Add real-time updates**: Consider polling for live game updates

## Notes

- ✅ All responses are **automatically cached** (5-60 min depending on data type)
- ✅ Rate limit: 5 requests/second (public API)
- ✅ No API key needed
- ✅ Free to use
- 💡 For production with high traffic, consider self-hosting (see full guide)

## Questions?

Check `NCAA_API_GUIDE.md` for:
- All available methods
- Response examples
- Error handling
- Caching strategies
- Self-hosting instructions
