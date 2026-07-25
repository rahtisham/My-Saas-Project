<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\Facebook\PostPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishSocialPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SocialPost $post,
    ) {
        $this->tries = config('social.publishing.max_retries', 3);
        $this->queue = config('social.publishing.queue', 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(PostPublisher $publisher): void
    {
        if (! $post->canBePublished()) {
            return;
        }

        $publisher->publish($this->post);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->post->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
