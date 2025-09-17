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
        Schema::table('banned_cards', function (Blueprint $table) {
            $table->foreign(['season_id'], 'banned_cards_ibfk_1')->references(['id'])->on('seasons')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['card_id'], 'banned_cards_ibfk_2')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['banned_by'], 'banned_cards_ibfk_3')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banned_cards', function (Blueprint $table) {
            $table->dropForeign('banned_cards_ibfk_1');
            $table->dropForeign('banned_cards_ibfk_2');
            $table->dropForeign('banned_cards_ibfk_3');
        });
    }
};
