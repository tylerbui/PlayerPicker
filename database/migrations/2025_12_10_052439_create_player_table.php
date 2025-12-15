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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            // API Integration
            $table->string('api_id')->unique(); // API-Sports player ID
            
            // Basic Info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('slug')->unique();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('birth_country')->nullable();
            $table->string('nationality')->nullable();
            $table->integer('height')->nullable(); // in cm
            $table->integer('weight')->nullable(); // in kg
            
            // Career Info
            $table->string('position')->nullable();
            $table->string('number')->nullable(); // Jersey number
            
            // Media
            $table->string('photo')->nullable();
            
            // Live Stats Cache (updated frequently)
            $table->json('current_season_stats')->nullable();
            $table->json('career_stats')->nullable();
            $table->timestamp('stats_synced_at')->nullable();
            
            // API Data Cache
            $table->json('extra_data')->nullable();
            $table->timestamp('synced_at')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Index for fast searches
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player');
    }
};
