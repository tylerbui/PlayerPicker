# NCAA Leagues Setup

## Overview
NCAA leagues have been added to PlayerPicker with proper organization by division and gender.

## What Was Created

### 1. Database Changes
- ✅ Added `description` field to `leagues` table
- ✅ Migration: `2025_12_15_201157_add_description_to_leagues_table.php`

### 2. Seeders Created
- ✅ `NcaaBasketballSeeder.php` - Creates 6 NCAA basketball leagues
- ✅ `NcaaFootballSeeder.php` - Creates 4 NCAA football leagues

### 3. Leagues Added

#### Basketball (7 total)
**Professional:**
- NBA

**College:**
- NCAA Men's Basketball (D1) - `ncaa-mens-d1`
- NCAA Men's Basketball (D2) - `ncaa-mens-d2`
- NCAA Men's Basketball (D3) - `ncaa-mens-d3`
- NCAA Women's Basketball (D1) - `ncaa-womens-d1`
- NCAA Women's Basketball (D2) - `ncaa-womens-d2`
- NCAA Women's Basketball (D3) - `ncaa-womens-d3`

#### Football (4 total)
**College:**
- NCAA Football (FBS) - `ncaa-football-fbs`
- NCAA Football (FCS) - `ncaa-football-fcs`
- NCAA Football (D2) - `ncaa-football-d2`
- NCAA Football (D3) - `ncaa-football-d3`

## How It Works

### League Structure
Each NCAA league has:
- **Name**: Display name (e.g., "NCAA Men's Basketball (D1)")
- **Slug**: URL-friendly identifier (e.g., `ncaa-mens-d1`)
- **Description**: Detailed info about the division
- **API ID**: Maps to NCAA API endpoints (e.g., `basketball-men-d1`)
- **API Type**: Set to `ncaa` for NCAA API integration
- **Category**: `college` (groups with other college leagues)
- **Logo**: NCAA official logo

### Frontend Display
When you visit `/sports/basketball`:
- **Professional** section shows: NBA
- **College** section shows: All 6 NCAA basketball divisions in circular grid format
- Each league shows its description below the name

## Re-seeding Leagues

To add/update NCAA leagues again:

```bash
# Basketball
php artisan db:seed --class=NcaaBasketballSeeder

# Football
php artisan db:seed --class=NcaaFootballSeeder
```

## Adding More NCAA Sports

You can create seeders for other NCAA sports following the same pattern:

### Example: NCAA Baseball

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sport;
use App\Models\League;

class NcaaBaseballSeeder extends Seeder
{
    public function run(): void
    {
        $baseball = Sport::firstOrCreate(
            ['slug' => 'baseball'],
            ['name' => 'Baseball', 'is_active' => true]
        );

        $ncaaLogo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/dd/NCAA_logo.svg/300px-NCAA_logo.svg.png';

        $leagues = [
            [
                'name' => 'NCAA Baseball (D1)',
                'slug' => 'ncaa-baseball-d1',
                'description' => 'NCAA Division I Baseball',
                'api_id' => 'baseball-d1',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            // Add D2, D3...
        ];

        foreach ($leagues as $leagueData) {
            League::updateOrCreate(
                ['slug' => $leagueData['slug']],
                array_merge($leagueData, [
                    'sport_id' => $baseball->id,
                    'is_active' => true,
                ])
            );
        }
    }
}
```

## Connecting to NCAA API

Each league's `api_id` maps directly to NCAA API endpoints:

```php
use App\Services\NcaaApiService;

// Get scores for NCAA Men's Basketball D1
$ncaaService = app(NcaaApiService::class);
$scores = $ncaaService->getScoreboard('basketball-men', 'd1', 2024, 'all', 'all-conf');

// Get standings
$standings = $ncaaService->getStandings('basketball-men', 'd1');
```

### API ID Mapping

| League | API Sport | API Division |
|--------|-----------|--------------|
| ncaa-mens-d1 | basketball-men | d1 |
| ncaa-womens-d1 | basketball-women | d1 |
| ncaa-football-fbs | football | fbs |
| ncaa-football-fcs | football | fcs |

## Frontend Components

The `SportDetail.vue` component automatically:
- Groups leagues by category (Professional, College, Amateur, Other)
- Displays NCAA logo in circular grid
- Shows description text below league name
- Applies hover effects and animations

## Database Schema

```sql
leagues table:
- id (primary key)
- sport_id (foreign key)
- name (string)
- slug (string, unique)
- description (text, nullable) ← NEW
- api_id (string)
- api_type (string)
- category (string) - 'college' for NCAA
- country (string)
- logo (string)
- is_active (boolean)
```

## Tips

### Custom Logo per Division
If you want different logos for Men's vs Women's:

```php
'logo' => 'https://example.com/mens-basketball-logo.png',
```

### Adding Conference Info
You could add conference as metadata:

```php
'description' => 'NCAA D1 Men\'s Basketball - ACC Conference',
```

### Filtering by Gender
Query leagues by name pattern:

```php
// Get only men's leagues
$mensLeagues = League::where('name', 'LIKE', "%Men's%")->get();

// Get only women's leagues
$womensLeagues = League::where('name', 'LIKE', "%Women's%")->get();
```

## Next Steps

1. **Add NCAA Teams** - Seed teams for each division
2. **Connect to NCAA API** - Fetch live scores and display on league pages
3. **Add More Sports** - Create seeders for Soccer, Lacrosse, Hockey, etc.
4. **Conference Pages** - Add sub-divisions for conferences (ACC, Big Ten, SEC, etc.)

---

**Status**: ✅ Complete and Ready
