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
        Schema::create('sports', function (Blueprint $table) {
            $table->id(); // primary key
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->string('api_name')->nullable();
            /**
             * types table --> team,individual,mixed
             */
            $table->string('type')->nullable();

            $table->string('icon')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
