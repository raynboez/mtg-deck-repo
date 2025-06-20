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
        Schema::table('deck_cards', function (Blueprint $table) {
            $table->foreign(['deck_id'], 'deck_cards_ibfk_1')->references(['deck_id'])->on('decks')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['card_id'], 'deck_cards_ibfk_2')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deck_cards', function (Blueprint $table) {
            $table->dropForeign('deck_cards_ibfk_1');
            $table->dropForeign('deck_cards_ibfk_2');
        });
    }
};
