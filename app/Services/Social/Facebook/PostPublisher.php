<?php

namespace App\Services\Social\Facebook;

use App\Models\SocialAccount;
use App\Models\SocialMedia;
use App\Models\SocialNotification;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;

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
                'status' => $retryCount >= $maxRetries ? 'failed' : 'scheduled',
                'failure_reason' => $e->getMessage(),
                'retry_count' => $retryCount,
            ]);

            if ($retryCount >= $maxRetries) {
                $this->notifyUser($post->user_id, 'post_failed', 'Post Failed', "Your post failed to publish after {$maxRetries} attempts: {$e->getMessage()}");
            }

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

        if ($firstMedia->type === 'image') {
            $data['url'] = $this->getMediaUrl($firstMedia);

            return $this->facebook->post("{$pageId}/photos", $account, $data);
        }

        if ($firstMedia->type === 'video') {
            $data['file_url'] = $this->getMediaUrl($firstMedia);
            $data['title'] = $post->caption ?? 'Video';

            return $this->facebook->post("{$pageId}/videos", $account, $data);
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
        $containerResponse = $this->createInstagramContainer($post, $account);

        $containerId = $containerResponse['id'];

        $result = $this->facebook->post("{$account->page_id}/media_publish", $account, [
            'creation_id' => $containerId,
        ]);

        return $result;
    }

    /**
     * Create an Instagram media container for publishing.
     *
     * @return array<string, mixed>
     */
    private function createInstagramContainer(SocialPost $post, SocialAccount $account): array
    {
        $media = $post->media->first();

        if (! $media) {
            throw new \RuntimeException('Instagram posts require at least one media item');
        }

        $data = [
            'image_url' => $this->getMediaUrl($media),
            'caption' => $post->caption ?? '',
        ];

        return $this->facebook->post("{$account->page_id}/media", $account, $data);
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
