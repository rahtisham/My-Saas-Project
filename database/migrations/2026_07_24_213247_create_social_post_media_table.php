<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_media_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['social_post_id', 'social_media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_media');
    }
};
