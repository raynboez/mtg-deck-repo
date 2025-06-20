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
        Schema::create('cards', function (Blueprint $table) {
            $table->integer('card_id', true);
            $table->string('card_name', 100);
            $table->string('mana_cost', 50)->nullable();
            $table->decimal('cmc', 4);
            $table->string('type_line', 100);
            $table->text('oracle_text')->nullable();
            $table->string('power', 10)->nullable();
            $table->string('toughness', 10)->nullable();
            $table->string('colours', 20)->nullable();
            $table->string('colour_identity', 20)->nullable();
            $table->string('image_url')->nullable();
            $table->string('scryfall_uri')->nullable();
            $table->string('set', 100)->nullable();
            $table->string('collector_number', 100)->nullable();
            $table->boolean('is_gamechanger')->nullable();
            $table->string('oracle_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
