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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->onDelete('cascade');
            
            // API Integration
            $table->string('api_id')->unique(); // API-Sports league ID
            $table->string('api_type')->nullable(); // 'league', 'cup', etc.
            
            // Basic Info
            $table->string('name'); // NBA, Premier League, NCAA
            $table->string('slug')->unique();
            $table->string('country')->nullable();
            $table->string('category')->nullable(); // professional, college, amateur
            
            // Media
            $table->string('logo')->nullable();
            $table->string('flag')->nullable(); // Country flag
            
            // API Data Cache
            $table->json('seasons')->nullable(); // Available seasons from API
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
        Schema::dropIfExists('leagues');
    }
};
