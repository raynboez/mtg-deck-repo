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
        Schema::table('overridden_cards', function (Blueprint $table) {
            $table->foreign(['season_id'], 'fk_overridden_cards_season_id')->references(['id'])->on('seasons')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['base_card_id'], 'fk_overridden_cards_base_card_id')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['override_card_id'], 'fk_overridden_cards_override_card_id')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['deck_id'], 'fk_overridden_cards_deck_id')->references(['deck_id'])->on('decks')->onUpdate('no action')->onDelete('no action');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overridden_cards', function (Blueprint $table) {
            $table->dropForeign('fk_overridden_cards_season_id');
            $table->dropForeign('fk_overridden_cards_base_card_id');
            $table->dropForeign('fk_overridden_cards_override_card_id');
            $table->dropForeign('fk_overridden_cards_deck_id');
        });
    }
};
