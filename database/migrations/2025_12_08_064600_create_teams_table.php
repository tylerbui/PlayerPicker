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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->onDelete('cascade');
            $table->foreignId('league_id')->nullable()->constrained()->onDelete('set null');
            
            // API Integration
            $table->string('api_id')->unique(); // API-Sports team ID
            
            // Basic Info
            $table->string('name'); // Lakers, Manchester United
            $table->string('slug')->unique();
            $table->string('code')->nullable(); // LAL, MUN (team abbreviation)
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->integer('founded')->nullable();
            $table->boolean('is_national')->default(false);
            
            // Venue Info (from API)
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_city')->nullable();
            $table->integer('venue_capacity')->nullable();
            $table->string('venue_surface')->nullable();
            $table->string('venue_image')->nullable();
            
            // Media
            $table->string('logo')->nullable();
            
            // API Data Cache
            $table->json('extra_data')->nullable(); // Store full API response
            $table->timestamp('synced_at')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
