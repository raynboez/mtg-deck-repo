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
        Schema::table('Deck_Cards', function (Blueprint $table) {
            $table->foreign(['deck_id'], 'Deck_Cards_ibfk_1')->references(['deck_id'])->on('Decks')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['card_id'], 'Deck_Cards_ibfk_2')->references(['card_id'])->on('Cards')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Deck_Cards', function (Blueprint $table) {
            $table->dropForeign('Deck_Cards_ibfk_1');
            $table->dropForeign('Deck_Cards_ibfk_2');
        });
    }
};
