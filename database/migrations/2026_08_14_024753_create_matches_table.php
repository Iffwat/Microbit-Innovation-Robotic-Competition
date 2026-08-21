<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            // Peringkat perlawanan
            $table->enum('stage', ['group', 'trophy_knockout', 'cup_knockout']);
            // Untuk perlawanan kumpulan
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            // Untuk perlawanan knockout
            $table->string('round_name')->nullable(); // R32, R16, QF, SF, 3rd, Final
            $table->integer('bracket_position')->nullable(); // slot dalam bracket
            // Pasukan
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            // Keputusan
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            // Status
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'walkover', 'bye'])->default('scheduled');
            // Jadual
            $table->integer('field_number')->nullable();
            $table->timestamp('scheduled_time')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
