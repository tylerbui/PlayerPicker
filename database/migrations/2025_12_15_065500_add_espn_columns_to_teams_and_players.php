<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('espn_team_id')->nullable()->unique()->after('api_id');
        });
        Schema::table('players', function (Blueprint $table) {
            $table->string('espn_athlete_id')->nullable()->unique()->after('api_id');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropUnique(['espn_athlete_id']);
            $table->dropColumn('espn_athlete_id');
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['espn_team_id']);
            $table->dropColumn('espn_team_id');
        });
    }
};