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
        Schema::create('Cards', function (Blueprint $table) {
            $table->string('card_id', 50)->primary();
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Cards');
    }
};
