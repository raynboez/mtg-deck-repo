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
        Schema::create('deck_cards', function (Blueprint $table) {
            $table->integer('prim_key', true);
            $table->integer('deck_id');
            $table->integer('card_id');
            $table->boolean('is_commander')->nullable()->default(false);
            $table->boolean('is_companion')->nullable()->default(false);
            $table->boolean('is_main_deck')->default(true);
            $table->boolean('is_sideboard')->nullable()->default(false);
            $table->integer('quantity')->nullable()->default(1);
            $table->primary(['prim_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deck_cards');
    }
};
