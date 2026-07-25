<?php

namespace App\Services\Social\Scheduler;

use App\Models\SocialPost;
use App\Services\Social\Facebook\PostPublisher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class PostScheduler
{
    public function __construct(
        private PostPublisher $publisher,
    ) {}

    /**
     * Process all scheduled posts that are due for publishing.
     */
    public function processDuePosts(): int
    {
        $duePosts = SocialPost::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->where('scheduled_at', '!=', null)
            ->with('socialAccount')
            ->get();

        $publishedCount = 0;

        foreach ($duePosts as $post) {
            try {
                $this->publisher->publish($post);
                $publishedCount++;

                Log::info('Scheduled post published', [
                    'post_id' => $post->id,
                    'platform' => $post->platform,
                ]);
            } catch (\Exception $e) {
                Log::error('Scheduled post failed', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $publishedCount;
    }

    /**
     * Get posts that are scheduled and due.
     *
     * @return Collection<int, SocialPost>
     */
    public function getDuePosts(): Collection
    {
        return SocialPost::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->where('scheduled_at', '!=', null)
            ->with('socialAccount')
            ->get();
    }

    /**
     * Get upcoming scheduled posts.
     *
     * @return Collection<int, SocialPost>
     */
    public function getUpcomingPosts(int $limit = 10): Collection
    {
        return SocialPost::where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->with('socialAccount')
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }
}
