<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knockout_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('bracket', ['trophy', 'cup']);
            $table->string('round');          // R32, R16, QF, SF, 3rd, Final
            $table->integer('round_order');   // 1=R32, 2=R16, 3=QF, 4=SF, 5=3rd, 6=Final
            $table->integer('slot_number');   // kedudukan dalam round (1,2,3...)
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->boolean('is_bye')->default(false);
            $table->timestamps();

            $table->unique(['category_id', 'bracket', 'round', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knockout_slots');
    }
};
