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
        Schema::create('match_participants', function (Blueprint $table) {
            $table->id('participant_id');
            $table->integer('match_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('deck_id');
            $table->boolean('is_winner');
            $table->integer('starting_life')->default(40);
            $table->integer('final_life')->nullable();
            $table->integer('turn_order');
            $table->integer('turn_lost');
            $table->integer('order_lost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};
