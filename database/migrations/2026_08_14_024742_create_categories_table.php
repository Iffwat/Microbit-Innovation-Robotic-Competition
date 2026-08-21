<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // U12, U15, U20, PPKI
            $table->string('slug')->unique();                // u12, u15, u20, ppki
            $table->enum('format', ['group_knockout', 'round_robin_only'])->default('group_knockout');
            $table->integer('teams_per_group')->default(5);
            $table->integer('fields_count')->default(1);    // bilangan padang
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
