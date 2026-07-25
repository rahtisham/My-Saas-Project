<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\SaveCampaignRequest;
use App\Models\SocialCampaign;
use App\Models\SocialPost;
use App\Models\Team;
use App\Services\Social\Facebook\CampaignManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SocialCampaignController extends Controller
{
    public function __construct(
        private CampaignManager $campaignManager,
    ) {}

    /**
     * Display a listing of the team's social campaigns.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [SocialCampaign::class, $currentTeam]);

        $campaigns = $currentTeam->socialCampaigns()
            ->with('socialPost')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialCampaign $campaign) => $this->formatCampaign($campaign));

        return Inertia::render('social/campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [SocialCampaign::class, $currentTeam]);

        $posts = $currentTeam->socialPosts()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (SocialPost $post) => [
                'id' => $post->id,
                'caption' => $post->caption,
                'platform' => $post->platform,
            ]);

        return Inertia::render('social/campaigns/Create', [
            'posts' => $posts,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(SaveCampaignRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialCampaign::class, $currentTeam]);

        $campaign = $currentTeam->socialCampaigns()->create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'social_post_id' => $request->social_post_id,
            'platform' => $request->platform,
            'budget' => $request->budget,
            'objective' => $request->objective,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'targeting' => $request->targeting,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign created.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(Team $currentTeam, SocialCampaign $campaign): Response
    {
        Gate::authorize('update', $campaign);

        $posts = $currentTeam->socialPosts()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (SocialPost $post) => [
                'id' => $post->id,
                'caption' => $post->caption,
                'platform' => $post->platform,
            ]);

        return Inertia::render('social/campaigns/Edit', [
            'campaign' => $this->formatCampaign($campaign->load('socialPost')),
            'posts' => $posts,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(SaveCampaignRequest $request, Team $currentTeam, SocialCampaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $campaign->update([
            'name' => $request->name,
            'description' => $request->description,
            'social_post_id' => $request->social_post_id,
            'platform' => $request->platform,
            'budget' => $request->budget,
            'objective' => $request->objective,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'targeting' => $request->targeting,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign updated.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Team $currentTeam, SocialCampaign $campaign): RedirectResponse
    {
        Gate::authorize('delete', $campaign);

        $campaign->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign deleted.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Pause the specified campaign.
     */
    public function pause(Team $currentTeam, SocialCampaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $this->campaignManager->pause($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign paused.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Resume the specified campaign.
     */
    public function resume(Team $currentTeam, SocialCampaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $this->campaignManager->resume($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign resumed.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Launch the specified campaign on the platform.
     */
    public function launch(Team $currentTeam, SocialCampaign $campaign): RedirectResponse
    {
        Gate::authorize('update', $campaign);

        $this->campaignManager->create($campaign);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Campaign launched.')]);

        return to_route('social.campaigns.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Format a campaign for the frontend.
     *
     * @return array<string, mixed>
     */
    private function formatCampaign(SocialCampaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'status' => $campaign->status,
            'platform' => $campaign->platform,
            'budget' => $campaign->budget,
            'spent' => $campaign->spent,
            'objective' => $campaign->objective,
            'startDate' => $campaign->start_date?->toIso8601String(),
            'endDate' => $campaign->end_date?->toIso8601String(),
            'platformCampaignId' => $campaign->platform_campaign_id,
            'targeting' => $campaign->targeting,
            'insights' => $campaign->insights,
            'failureReason' => $campaign->failure_reason,
            'socialPost' => $campaign->socialPost ? [
                'id' => $campaign->socialPost->id,
                'caption' => $campaign->socialPost->caption,
                'platform' => $campaign->socialPost->platform,
            ] : null,
            'createdAt' => $campaign->created_at->toIso8601String(),
        ];
    }
}
