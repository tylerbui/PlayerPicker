<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sport;
use App\Models\League;

class NcaaBasketballSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $basketball = Sport::where('slug', 'basketball')->first();

        if (!$basketball) {
            $this->command->error('Basketball sport not found. Please create it first.');
            return;
        }

        $ncaaLogo = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/dd/NCAA_logo.svg/300px-NCAA_logo.svg.png';

        $leagues = [
            // Men's Basketball
            [
                'name' => 'NCAA Men\'s Basketball (D1)',
                'slug' => 'ncaa-mens-d1',
                'description' => 'NCAA Division I Men\'s Basketball - Top tier college basketball with 350+ teams',
                'api_id' => 'basketball-men-d1',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Men\'s Basketball (D2)',
                'slug' => 'ncaa-mens-d2',
                'description' => 'NCAA Division II Men\'s Basketball',
                'api_id' => 'basketball-men-d2',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Men\'s Basketball (D3)',
                'slug' => 'ncaa-mens-d3',
                'description' => 'NCAA Division III Men\'s Basketball',
                'api_id' => 'basketball-men-d3',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            
            // Women's Basketball
            [
                'name' => 'NCAA Women\'s Basketball (D1)',
                'slug' => 'ncaa-womens-d1',
                'description' => 'NCAA Division I Women\'s Basketball - Top tier women\'s college basketball',
                'api_id' => 'basketball-women-d1',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Women\'s Basketball (D2)',
                'slug' => 'ncaa-womens-d2',
                'description' => 'NCAA Division II Women\'s Basketball',
                'api_id' => 'basketball-women-d2',
                'api_type' => 'ncaa',
                'category' => 'college',
                'country' => 'USA',
                'logo' => $ncaaLogo,
            ],
            [
                'name' => 'NCAA Women\'s Basketball (D3)',
                'slug' => 'ncaa-womens-d3',
                'description' => 'NCAA Division III Women\'s Basketball',
                'api_id' => 'basketball-women-d3',
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
                    'sport_id' => $basketball->id,
                    'is_active' => true,
                ])
            );

            $this->command->info("✓ {$league->name}");
        }

        $this->command->info("\n✅ NCAA Basketball leagues seeded successfully!");
    }
}
