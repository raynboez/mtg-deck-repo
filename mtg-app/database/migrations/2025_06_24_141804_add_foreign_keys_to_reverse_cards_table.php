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
        Schema::table('reverse_cards', function (Blueprint $table) {
            $table->foreign(['face_card_id'], 'fk_card_id')->references(['card_id'])->on('cards')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reverse_cards', function (Blueprint $table) {
            $table->dropForeign('fk_card_id');
        });
    }
};
