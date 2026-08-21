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
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique('teams_category_id_team_name_unique');
            
            // Re-add foreign key which will auto-create a standard index for it
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            
            $table->unique(['game_type', 'category_id', 'team_name'], 'teams_game_cat_team_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique('teams_game_cat_team_unique');
            
            $table->unique(['category_id', 'team_name'], 'teams_category_id_team_name_unique');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }
};
