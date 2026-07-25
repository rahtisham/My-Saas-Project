<?php

use App\Http\Controllers\Social\SocialAccountController;
use App\Http\Controllers\Social\SocialCampaignController;
use App\Http\Controllers\Social\SocialDashboardController;
use App\Http\Controllers\Social\SocialMediaController;
use App\Http\Controllers\Social\SocialNotificationController;
use App\Http\Controllers\Social\SocialPostController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}/social')->name('social.')->middleware(['auth', 'verified', EnsureTeamMembership::class])->group(function () {

    // Dashboard
    Route::get('/', SocialDashboardController::class)->name('dashboard');

    // Social Accounts
    Route::get('accounts', [SocialAccountController::class, 'index'])->name('accounts.index');
    Route::post('accounts', [SocialAccountController::class, 'store'])->name('accounts.store');
    Route::get('accounts/redirect', [SocialAccountController::class, 'redirect'])->name('accounts.redirect');
    Route::get('accounts/callback', [SocialAccountController::class, 'callback'])->name('accounts.callback');
    Route::delete('accounts/{account}', [SocialAccountController::class, 'destroy'])->name('accounts.destroy');

    // Media
    Route::get('media', [SocialMediaController::class, 'index'])->name('media.index');
    Route::get('media/create', [SocialMediaController::class, 'create'])->name('media.create');
    Route::post('media', [SocialMediaController::class, 'store'])->name('media.store');
    Route::delete('media/{media}', [SocialMediaController::class, 'destroy'])->name('media.destroy');

    // Posts
    Route::get('posts', [SocialPostController::class, 'index'])->name('posts.index');
    Route::get('posts/create', [SocialPostController::class, 'create'])->name('posts.create');
    Route::post('posts', [SocialPostController::class, 'store'])->name('posts.store');
    Route::get('posts/{post}/edit', [SocialPostController::class, 'edit'])->name('posts.edit');
    Route::put('posts/{post}', [SocialPostController::class, 'update'])->name('posts.update');
    Route::delete('posts/{post}', [SocialPostController::class, 'destroy'])->name('posts.destroy');
    Route::post('posts/{post}/publish', [SocialPostController::class, 'publish'])->name('posts.publish');

    // Campaigns
    Route::get('campaigns', [SocialCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campaigns/create', [SocialCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('campaigns', [SocialCampaignController::class, 'store'])->name('campaigns.store');
    Route::get('campaigns/{campaign}/edit', [SocialCampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('campaigns/{campaign}', [SocialCampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('campaigns/{campaign}', [SocialCampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('campaigns/{campaign}/pause', [SocialCampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/resume', [SocialCampaignController::class, 'resume'])->name('campaigns.resume');
    Route::post('campaigns/{campaign}/launch', [SocialCampaignController::class, 'launch'])->name('campaigns.launch');

    // Notifications
    Route::get('notifications', [SocialNotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [SocialNotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('notifications/read-all', [SocialNotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});
