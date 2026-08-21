<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            
            // Core team info
            $table->string('team_name');
            $table->string('school_name')->nullable();
            
            // Players
            $table->string('player_1')->nullable();
            $table->string('player_2')->nullable();
            $table->string('player_3')->nullable();

            // Status and attendance
            $table->enum('status', ['registered', 'checked_in', 'absent'])->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();

            $table->timestamps();

            // Prevent duplicate team name in the same category
            $table->unique(['category_id', 'team_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
