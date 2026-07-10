<?php

use App\Enums\GameMode;
use App\Enums\SubfactionAstartes;
use App\Enums\SubfactionChaos;
use App\Enums\SubfactionImperium;
use App\Enums\SubfactionXenos;
use App\Enums\Faction;
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
        Schema::create('armies', function (Blueprint $table) {
            $table->id('army_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign(['user_id'], 'fk_army_user_id')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->text('name');
            $table->text('description')->nullable();
            $table->enum('game_mode', array_column(GameMode::cases(), 'value'));
            $table->integer('points')->nullable();
            $table->enum('faction', array_column(Faction::cases(), 'value'));
            $table->text('subfaction');
            $table->text('army_link')->nullable();
            $table->text('list')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('armies', function (Blueprint $table) {
            $table->dropForeign('fk_army_user_id');
        });
        Schema::dropIfExists('armies');
    }
};
