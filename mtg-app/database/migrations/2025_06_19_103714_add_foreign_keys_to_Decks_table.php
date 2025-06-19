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
        Schema::table('Decks', function (Blueprint $table) {
            $table->foreign(['user_id'], 'Decks_ibfk_1')->references(['user_id'])->on('Users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Decks', function (Blueprint $table) {
            $table->dropForeign('Decks_ibfk_1');
        });
    }
};
