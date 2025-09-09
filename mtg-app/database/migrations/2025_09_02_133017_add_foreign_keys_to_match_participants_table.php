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
        Schema::table('match_participants', function (Blueprint $table) {
            $table->foreign(['match_id'], 'fk_match_id')->references(['match_id'])->on('matches')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_match_user_id')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['deck_id'], 'fk_match_deck_id')->references(['deck_id'])->on('decks')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_participants', function (Blueprint $table) {
            $table->dropForeign('fk_match_id');
            $table->dropForeign('fk_match_user_id');
            $table->dropForeign('fk_match_deck_id');
        });
    }
};
