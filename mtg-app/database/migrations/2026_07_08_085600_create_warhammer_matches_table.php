<?php

use App\Enums\GameMode;
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
        Schema::create('warhammer_matches', function (Blueprint $table) {
            $table->id('match_id');
            $table->enum('game_mode', array_column(GameMode::cases(), 'value'));
            $table->integer('number_of_players');
            $table->text('notes')->nullable();
            $table->timestamp('played_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warhammer_matches');
    }
};
