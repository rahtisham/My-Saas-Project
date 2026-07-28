<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\SavePostRequest;
use App\Jobs\PublishSocialPost;
use App\Models\SocialAccount;
use App\Models\SocialMedia;
use App\Models\SocialPost;
use App\Models\Team;
use App\Services\Social\Facebook\PostPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SocialPostController extends Controller
{
    /**
     * Display a listing of the team's social posts.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [SocialPost::class, $currentTeam]);

        $posts = $currentTeam->socialPosts()
            ->with(['socialAccount', 'media'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialPost $post) => $this->formatPost($post));

        return Inertia::render('social/posts/Index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [SocialPost::class, $currentTeam]);

        $accounts = $currentTeam->socialAccounts()
            ->where('is_active', true)
            ->get()
            ->map(fn (SocialAccount $account) => [
                'id' => $account->id,
                'platform' => $account->platform,
                'name' => $account->name,
            ]);

        $media = $currentTeam->socialMedia()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialMedia $item) => [
                'id' => $item->id,
                'fileName' => $item->file_name,
                'type' => $item->type,
                'url' => $item->url,
            ]);

        return Inertia::render('social/posts/Create', [
            'accounts' => $accounts,
            'media' => $media,
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(SavePostRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialPost::class, $currentTeam]);

        $post = $currentTeam->socialPosts()->create([
            'user_id' => auth()->id(),
            'social_account_id' => $request->social_account_id,
            'caption' => $request->caption,
            'platform' => $request->platform,
            'status' => $request->status,
            'visibility' => $request->visibility,
            'scheduled_at' => $request->scheduled_at,
        ]);

        if ($request->has('media_ids') && $request->media_ids !== []) {
            $post->media()->sync($request->media_ids);
        }

        if ($request->status === 'scheduled' && $request->scheduled_at) {
            PublishSocialPost::dispatch($post)->delay($request->scheduled_at);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('social.posts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Team $currentTeam, SocialPost $post): Response
    {
        Gate::authorize('update', $post);

        $accounts = $currentTeam->socialAccounts()
            ->where('is_active', true)
            ->get()
            ->map(fn (SocialAccount $account) => [
                'id' => $account->id,
                'platform' => $account->platform,
                'name' => $account->name,
            ]);

        $media = $currentTeam->socialMedia()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialMedia $item) => [
                'id' => $item->id,
                'fileName' => $item->file_name,
                'type' => $item->type,
                'url' => $item->url,
            ]);

        return Inertia::render('social/posts/Edit', [
            'post' => $this->formatPost($post->load('media')),
            'accounts' => $accounts,
            'media' => $media,
        ]);
    }

    /**
     * Update the specified post.
     */
    public function update(SavePostRequest $request, Team $currentTeam, SocialPost $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $post->update([
            'social_account_id' => $request->social_account_id,
            'caption' => $request->caption,
            'platform' => $request->platform,
            'status' => $request->status,
            'visibility' => $request->visibility,
            'scheduled_at' => $request->scheduled_at,
        ]);

        if ($request->has('media_ids')) {
            $post->media()->sync($request->media_ids);
        }

        if ($request->status === 'scheduled' && $request->scheduled_at) {
            PublishSocialPost::dispatch($post)->delay($request->scheduled_at);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('social.posts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Team $currentTeam, SocialPost $post): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

        return to_route('social.posts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Publish a post immediately.
     */
    public function publish(Team $currentTeam, SocialPost $post): RedirectResponse
    {
        Gate::authorize('publish', $post);

        $post->update(['retry_count' => 0, 'status' => 'draft']);

        try {
            app(PostPublisher::class)->publish($post);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Post published successfully.')]);
        } catch (\Throwable $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Publish failed: :reason', ['reason' => $e->getMessage()])]);
        }

        return to_route('social.posts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Format a post for the frontend.
     *
     * @return array<string, mixed>
     */
    private function formatPost(SocialPost $post): array
    {
        return [
            'id' => $post->id,
            'caption' => $post->caption,
            'platform' => $post->platform,
            'status' => $post->status,
            'visibility' => $post->visibility,
            'scheduledAt' => $post->scheduled_at?->toIso8601String(),
            'publishedAt' => $post->published_at?->toIso8601String(),
            'platformPostId' => $post->platform_post_id,
            'failureReason' => $post->failure_reason,
            'retryCount' => $post->retry_count,
            'socialAccount' => [
                'id' => $post->socialAccount->id,
                'name' => $post->socialAccount->name,
                'platform' => $post->socialAccount->platform,
            ],
            'media' => $post->media->map(fn (SocialMedia $item) => [
                'id' => $item->id,
                'fileName' => $item->file_name,
                'type' => $item->type,
                'url' => $item->url,
            ]),
            'createdAt' => $post->created_at->toIso8601String(),
        ];
    }
}
