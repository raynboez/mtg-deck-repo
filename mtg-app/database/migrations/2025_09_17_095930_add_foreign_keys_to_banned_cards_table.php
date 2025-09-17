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
            $table->foreign(['season_id'], 'fk_season_id')->references(['id'])->on('seasons')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['card_id'], 'fk_banned_card_id')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['banned_by'], 'fk_banned_by_id')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banned_cards', function (Blueprint $table) {
            $table->dropForeign('fk_season_id');
            $table->dropForeign('fk_banned_card_id');
            $table->dropForeign('fk_banned_by_id');
        });
    }
};
