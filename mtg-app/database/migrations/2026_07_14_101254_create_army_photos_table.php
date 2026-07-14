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
        Schema::create('army_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('army_id');
            $table->foreign(['user_id'], 'fk_army_photo_user_id')->references(['user_id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['army_id'], 'fk_army_photo_army_id')->references(['army_id'])->on('armies')->onUpdate('no action')->onDelete('no action');
            $table->string('photo_url');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->index(['army_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('army_photos', function (Blueprint $table) {
            $table->dropForeign('fk_army_photo_user_id');
            $table->dropForeign('fk_army_photo_army_id');
        });
        Schema::dropIfExists('army_photos');
    }
};
