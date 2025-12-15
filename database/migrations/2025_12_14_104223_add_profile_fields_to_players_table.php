<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->text('biography')->nullable()->after('photo');
            $table->json('recent_games_stats')->nullable()->after('current_season_stats');
            $table->json('previous_season_stats')->nullable()->after('recent_games_stats');
            $table->json('news')->nullable()->after('stats_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['biography', 'recent_games_stats', 'previous_season_stats', 'news']);
        });
    }
};
