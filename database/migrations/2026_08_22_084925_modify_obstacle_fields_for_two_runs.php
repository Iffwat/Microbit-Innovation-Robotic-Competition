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
        Schema::table('matches', function (Blueprint $table) {
            $table->renameColumn('obstacle_time_ms', 'obstacle_time_ms_1');
            $table->renameColumn('obstacle_penalties', 'obstacle_penalties_1');
            
            $table->integer('obstacle_time_ms_2')->nullable()->after('obstacle_penalties_1');
            $table->integer('obstacle_penalties_2')->nullable()->after('obstacle_time_ms_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->renameColumn('obstacle_time_ms_1', 'obstacle_time_ms');
            $table->renameColumn('obstacle_penalties_1', 'obstacle_penalties');
            
            $table->dropColumn(['obstacle_time_ms_2', 'obstacle_penalties_2']);
        });
    }
};
