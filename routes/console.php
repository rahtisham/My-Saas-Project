<?php

use App\Jobs\SyncAllCampaignInsights;
use App\Models\TeamInvitation;
use App\Services\Social\Scheduler\PostScheduler;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::call(function () {
    app(PostScheduler::class)->processDuePosts();
})->everyMinute()->description('Process scheduled social media posts');

Schedule::job(new SyncAllCampaignInsights)
    ->hourly()
    ->description('Sync campaign insights from Facebook/Instagram');
