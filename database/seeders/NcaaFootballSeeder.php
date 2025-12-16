<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sport;
use App\Models\League;

class NcaaFootballSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, ensure Football sport exists
        $football = Sport::firstOrCreate(
            ['slug' => 'football'],
            [
                'name' => 'Football',
                'description' => 'American Football',
                'is_active' => true,
            ]
        );

        $ncaaLogo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/dd/NCAA_logo.svg/300px-NCAA_logo.svg.png';

        $leagues = [
            [
                'name' => 'NCAA Football (FBS)',
                'slug' => 'ncaa-football-fbs',
                'description' => 'NCAA Division I FBS (Football Bowl Subdivision) - Highest level of college football',
                'api_id' => 'football-fbs',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Football (FCS)',
                'slug' => 'ncaa-football-fcs',
                'description' => 'NCAA Division I FCS (Football Championship Subdivision)',
                'api_id' => 'football-fcs',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Football (D2)',
                'slug' => 'ncaa-football-d2',
                'description' => 'NCAA Division II Football',
                'api_id' => 'football-d2',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Football (D3)',
                'slug' => 'ncaa-football-d3',
                'description' => 'NCAA Division III Football',
                'api_id' => 'football-d3',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
        ];

        foreach ($leagues as $leagueData) {
            $league = League::updateOrCreate(
                ['slug' => $leagueData['slug']],
                array_merge($leagueData, [
                    'sport_id' => $football->id,
                    'is_active' => true,
                ])
            );

            $this->command->info("✓ {$league->name}");
        }

        $this->command->info("\n✅ NCAA Football leagues seeded successfully!");
    }
}
