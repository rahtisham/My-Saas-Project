<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('platform')->comment('facebook or instagram');
            $table->string('platform_user_id')->comment('Facebook/Instagram user ID');
            $table->string('name');
            $table->string('page_id')->nullable()->comment('Facebook Page ID or Instagram Business Account ID');
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'platform', 'platform_user_id']);
            $table->index(['team_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
