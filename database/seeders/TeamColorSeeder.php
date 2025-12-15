<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamColorSeeder extends Seeder
{
    /**
     * NBA Team Colors - Official brand colors
     */
    protected array $nbaColors = [
        'Atlanta Hawks' => ['#E03A3E', '#C1D32F'],
        'Boston Celtics' => ['#007A33', '#BA9653'],
        'Brooklyn Nets' => ['#000000', '#FFFFFF'],
        'Charlotte Hornets' => ['#1D1160', '#00788C'],
        'Chicago Bulls' => ['#CE1141', '#000000'],
        'Cleveland Cavaliers' => ['#860038', '#FDBB30'],
        'Dallas Mavericks' => ['#00538C', '#002B5E'],
        'Denver Nuggets' => ['#0E2240', '#FEC524'],
        'Detroit Pistons' => ['#C8102E', '#1D42BA'],
        'Golden State Warriors' => ['#1D428A', '#FFC72C'],
        'Houston Rockets' => ['#CE1141', '#000000'],
        'Indiana Pacers' => ['#002D62', '#FDBB30'],
        'LA Clippers' => ['#C8102E', '#1D428A'],
        'Los Angeles Lakers' => ['#552583', '#FDB927'],
        'Memphis Grizzlies' => ['#5D76A9', '#12173F'],
        'Miami Heat' => ['#98002E', '#F9A01B'],
        'Milwaukee Bucks' => ['#00471B', '#EEE1C6'],
        'Minnesota Timberwolves' => ['#0C2340', '#236192'],
        'New Orleans Pelicans' => ['#0C2340', '#C8102E'],
        'New York Knicks' => ['#006BB6', '#F58426'],
        'Oklahoma City Thunder' => ['#007AC1', '#EF3B24'],
        'Orlando Magic' => ['#0077C0', '#C4CED4'],
        'Philadelphia 76ers' => ['#006BB6', '#ED174C'],
        'Phoenix Suns' => ['#1D1160', '#E56020'],
        'Portland Trail Blazers' => ['#E03A3E', '#000000'],
        'Sacramento Kings' => ['#5A2D81', '#63727A'],
        'San Antonio Spurs' => ['#C4CED4', '#000000'],
        'Toronto Raptors' => ['#CE1141', '#000000'],
        'Utah Jazz' => ['#002B5C', '#00471B'],
        'Washington Wizards' => ['#002B5C', '#E31837'],
    ];

    public function run(): void
    {
        foreach ($this->nbaColors as $teamName => $colors) {
            Team::where('name', 'like', "%{$teamName}%")
                ->orWhere('name', 'like', '%' . str_replace(' ', '%', $teamName) . '%')
                ->update([
                    'primary_color' => $colors[0],
                    'secondary_color' => $colors[1],
                ]);
        }

        $this->command->info('✅ Team colors updated successfully!');
    }
}
