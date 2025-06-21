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
        Schema::create('warhammer_match_participants', function (Blueprint $table) {
            $table->id('participant_id');
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('army_id');
            $table->foreign(['match_id'], 'fk_warhammer_match_id')->references(['match_id'])->on('warhammer_matches')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'fk_warhammer_match_user_id')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['army_id'], 'fk_warhammer_match_army_id')->references(['army_id'])->on('armies')->onUpdate('no action')->onDelete('no action');
            $table->boolean('is_winner');
            $table->integer('victory_points')->default(0);
            $table->integer('primary_points')->default(0)->nullable();
            $table->integer('secondary_points')->default(0)->nullable();
            $table->integer('tertiary_points')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warhammer_match_participants', function (Blueprint $table) {
            $table->dropForeign('fk_warhammer_match_id');
            $table->dropForeign('fk_warhammer_match_user_id');
            $table->dropForeign('fk_warhammer_match_army_id');
        });
        Schema::dropIfExists('warhammer_match_participants');
    }
};
