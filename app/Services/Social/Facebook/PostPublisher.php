<?php

namespace App\Services\Social\Facebook;

use App\Models\SocialAccount;
use App\Models\SocialMedia;
use App\Models\SocialNotification;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostPublisher
{
    public function __construct(
        private FacebookService $facebook,
    ) {}

    /**
     * Publish a post to Facebook or Instagram.
     */
    public function publish(SocialPost $post): array
    {
        $account = $post->socialAccount;

        if ($account->isTokenExpired()) {
            throw new \RuntimeException('Social account access token has expired. Please reconnect the account.');
        }

        $post->update(['status' => 'publishing']);

        try {
            $result = match ($post->platform) {
                'facebook' => $this->publishToFacebook($post, $account),
                'instagram' => $this->publishToInstagram($post, $account),
                default => throw new \RuntimeException("Unsupported platform: {$post->platform}"),
            };

            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'platform_post_id' => $result['id'] ?? null,
                'platform_response' => $result,
                'failure_reason' => null,
            ]);

            $this->notifyUser($post->user_id, 'post_published', 'Post Published', "Your post has been published to {$post->platform}.");

            return $result;
        } catch (\Exception $e) {
            Log::error('Post publishing failed', [
                'post_id' => $post->id,
                'platform' => $post->platform,
                'error' => $e->getMessage(),
            ]);

            $retryCount = $post->retry_count + 1;
            $maxRetries = config('social.publishing.max_retries', 3);

            $post->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'retry_count' => $retryCount,
            ]);

            $this->notifyUser($post->user_id, 'post_failed', 'Post Failed', "Your post failed to publish: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Publish a post to Facebook Page.
     *
     * @return array<string, mixed>
     */
    private function publishToFacebook(SocialPost $post, SocialAccount $account): array
    {
        $pageId = $account->page_id;

        $data = [
            'message' => $post->caption,
        ];

        $media = $post->media;

        if ($media->isEmpty()) {
            return $this->facebook->post("{$pageId}/feed", $account, $data);
        }

        $firstMedia = $media->first();
        $filePath = Storage::disk('social')->path($firstMedia->file_path);

        if ($firstMedia->type === 'image') {
            return $this->facebook->upload("{$pageId}/photos", $account, $filePath, 'source', $data);
        }

        if ($firstMedia->type === 'video') {
            $data['title'] = $post->caption ?? 'Video';

            return $this->facebook->upload("{$pageId}/videos", $account, $filePath, 'source', $data);
        }

        return $this->facebook->post("{$pageId}/feed", $account, $data);
    }

    /**
     * Publish a post to Instagram Business Account.
     *
     * @return array<string, mixed>
     */
    private function publishToInstagram(SocialPost $post, SocialAccount $account): array
    {
        $igAccountId = $account->instagram_account_id;

        if (! $igAccountId) {
            throw new \RuntimeException('No Instagram Business Account linked to this Facebook page. Please reconnect your account from the Accounts page to link an Instagram account.');
        }

        $containerResponse = $this->createInstagramContainer($post, $account, $igAccountId);

        $containerId = $containerResponse['id'];

        $result = $this->facebook->post("{$igAccountId}/media_publish", $account, [
            'creation_id' => $containerId,
        ]);

        return $result;
    }

    /**
     * Create an Instagram media container for publishing.
     *
     * @return array<string, mixed>
     */
    private function createInstagramContainer(SocialPost $post, SocialAccount $account, string $igAccountId): array
    {
        $media = $post->media->first();

        if (! $media) {
            throw new \RuntimeException('Instagram posts require at least one media item');
        }

        $filePath = Storage::disk('social')->path($media->file_path);

        $data = [
            'caption' => $post->caption ?? '',
        ];

        return $this->facebook->upload("{$igAccountId}/media", $account, $filePath, 'source', $data);
    }

    /**
     * Get the full URL for a media file.
     */
    private function getMediaUrl(SocialMedia $media): string
    {
        return $media->url;
    }

    /**
     * Create a database notification for the user.
     */
    private function notifyUser(int $userId, string $type, string $title, string $message): void
    {
        SocialNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }
}
