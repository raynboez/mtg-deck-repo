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
            $table->integer('mmr_before')->nullable();
            $table->integer('mmr_after')->nullable();
            $table->integer('mmr_change')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_participants', function (Blueprint $table) {
            $table->dropColumn('mmr_before');
            $table->dropColumn('mmr_after');
            $table->dropColumn('mmr_change');
        });
    }
};
