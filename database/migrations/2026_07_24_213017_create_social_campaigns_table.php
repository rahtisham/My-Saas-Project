<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->comment('draft, active, paused, completed, failed');
            $table->string('platform')->comment('facebook or instagram');
            $table->decimal('budget', 10, 2)->nullable()->comment('Budget in USD');
            $table->decimal('spent', 10, 2)->default(0);
            $table->string('objective')->nullable()->comment('Engagement, Traffic, Conversions, etc.');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('platform_campaign_id')->nullable()->comment('Campaign ID from Facebook API');
            $table->json('targeting')->nullable()->comment('Audience targeting configuration');
            $table->json('insights')->nullable()->comment('Performance metrics synced from API');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_campaigns');
    }
};
