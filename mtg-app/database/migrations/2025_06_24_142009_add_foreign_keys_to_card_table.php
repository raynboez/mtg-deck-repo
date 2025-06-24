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
        Schema::table('card', function (Blueprint $table) {
            $table->foreign(['reverse_card_id'], 'fk_reverse_card_id')->references(['card_id'])->on('reverse_cards')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card', function (Blueprint $table) {
            $table->dropForeign('fk_reverse_card_id');
        });
    }
};
