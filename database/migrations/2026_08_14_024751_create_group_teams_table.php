<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            // Statistik perlawanan kumpulan
            $table->integer('played')->default(0);
            $table->integer('won')->default(0);
            $table->integer('drawn')->default(0);
            $table->integer('lost')->default(0);
            $table->integer('goals_for')->default(0);
            $table->integer('goals_against')->default(0);
            $table->integer('goal_difference')->default(0);
            $table->integer('points')->default(0);
            // Kedudukan akhir dalam kumpulan (1–5), null sebelum selesai
            $table->integer('position')->nullable();
            // Kelayakan: trophy (1&2), cup (3&4), none (>4)
            $table->enum('qualification', ['trophy', 'cup', 'none', 'pending'])->default('pending');
            $table->timestamps();

            $table->unique(['group_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_teams');
    }
};
