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
        Schema::create('decks', function (Blueprint $table) {
            $table->integer('deck_id', true);
            $table->unsignedBigInteger('user_id')->nullable()->index('fk_user_id');
            $table->string('deck_name', 100);
            $table->text('description')->nullable();
            $table->text('colour_identity')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->boolean('is_public')->nullable()->default(true);
            $table->integer('power_level')->nullable()->default(2);
            $table->boolean('is_paper')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decks');
    }
};
