<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class SocialDashboardController extends Controller
{
    /**
     * Display the social media dashboard.
     */
    public function __invoke(Team $currentTeam): Response
    {
        $totalPosts = $currentTeam->socialPosts()->count();
        $publishedPosts = $currentTeam->socialPosts()->where('status', 'published')->count();
        $scheduledPosts = $currentTeam->socialPosts()->where('status', 'scheduled')->count();
        $failedPosts = $currentTeam->socialPosts()->where('status', 'failed')->count();
        $totalMedia = $currentTeam->socialMedia()->count();
        $activeCampaigns = $currentTeam->socialCampaigns()->where('status', 'active')->count();
        $connectedAccounts = $currentTeam->socialAccounts()->where('is_active', true)->count();

        $recentPosts = $currentTeam->socialPosts()
            ->with('socialAccount')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (SocialPost $post) => [
                'id' => $post->id,
                'caption' => $post->caption,
                'platform' => $post->platform,
                'status' => $post->status,
                'socialAccount' => [
                    'name' => $post->socialAccount->name,
                    'platform' => $post->socialAccount->platform,
                ],
                'createdAt' => $post->created_at->toIso8601String(),
            ]);

        return Inertia::render('social/Dashboard', [
            'stats' => [
                'totalPosts' => $totalPosts,
                'publishedPosts' => $publishedPosts,
                'scheduledPosts' => $scheduledPosts,
                'failedPosts' => $failedPosts,
                'totalMedia' => $totalMedia,
                'activeCampaigns' => $activeCampaigns,
                'connectedAccounts' => $connectedAccounts,
            ],
            'recentPosts' => $recentPosts,
        ]);
    }
}
