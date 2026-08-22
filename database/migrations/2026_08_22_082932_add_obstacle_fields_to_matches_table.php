<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->integer('obstacle_time_ms')->nullable()->after('away_score');
            $table->integer('obstacle_penalties')->nullable()->after('obstacle_time_ms');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['obstacle_time_ms', 'obstacle_penalties']);
        });
    }
};
