<?php

namespace App\Services\Social\Facebook;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService
{
    private string $graphVersion;

    private string $baseUrl;

    public function __construct()
    {
        $this->graphVersion = config('social.facebook.graph_version', 'v21.0');
        $this->baseUrl = "https://graph.facebook.com/{$this->graphVersion}";
    }

    /**
     * Get the Facebook Graph API base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Make a GET request to the Facebook Graph API.
     *
     * @return array<string, mixed>
     */
    public function get(string $endpoint, SocialAccount $account, array $params = []): array
    {
        $params['access_token'] = $account->access_token;

        $response = Http::get("{$this->baseUrl}/{$endpoint}", $params);

        if ($response->failed()) {
            Log::error('Facebook API GET failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException('Facebook API request failed: '.$response->json('error.message', 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Make a POST request to the Facebook Graph API.
     *
     * @return array<string, mixed>
     */
    public function post(string $endpoint, SocialAccount $account, array $data = []): array
    {
        $data['access_token'] = $account->access_token;

        $response = Http::post("{$this->baseUrl}/{$endpoint}", $data);

        if ($response->failed()) {
            Log::error('Facebook API POST failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException('Facebook API request failed: '.$response->json('error.message', 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Upload a file via multipart POST to the Facebook Graph API.
     *
     * @return array<string, mixed>
     */
    public function upload(string $endpoint, SocialAccount $account, string $filePath, string $fieldName, array $data = []): array
    {
        $data['access_token'] = $account->access_token;

        $fileSize = filesize($filePath);
        $timeout = $fileSize > 10 * 1024 * 1024 ? 300 : 120;

        $response = Http::timeout($timeout)
            ->attach(
                $fieldName,
                file_get_contents($filePath),
                basename($filePath)
            )->post("{$this->baseUrl}/{$endpoint}", $data);

        if ($response->failed()) {
            Log::error('Facebook API upload failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException('Facebook API upload failed: '.$response->json('error.message', 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Make a DELETE request to the Facebook Graph API.
     *
     * @return array<string, mixed>
     */
    public function delete(string $endpoint, SocialAccount $account, array $params = []): array
    {
        $params['access_token'] = $account->access_token;

        $response = Http::delete("{$this->baseUrl}/{$endpoint}", $params);

        if ($response->failed()) {
            Log::error('Facebook API DELETE failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException('Facebook API request failed: '.$response->json('error.message', 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Get page access token from user access token.
     */
    public function getPageAccessToken(string $userAccessToken, string $pageId): string
    {
        $response = Http::get("{$this->baseUrl}/me/accounts", [
            'access_token' => $userAccessToken,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch page access token');
        }

        $pages = $response->json('data', []);

        foreach ($pages as $page) {
            if ($page['id'] === $pageId) {
                return $page['access_token'];
            }
        }

        throw new \RuntimeException("Page {$pageId} not found or not accessible");
    }

    /**
     * Get Instagram business account ID from a Facebook page.
     */
    public function getInstagramBusinessAccountId(string $pageAccessToken, string $pageId): ?string
    {
        $response = Http::get("{$this->baseUrl}/{$pageId}", [
            'access_token' => $pageAccessToken,
            'fields' => 'instagram_business_account',
        ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('instagram_business_account.id');
    }

    /**
     * Validate that an access token is still valid.
     */
    public function validateToken(SocialAccount $account): bool
    {
        try {
            $response = Http::get("{$this->baseUrl}/me", [
                'access_token' => $account->access_token,
                'fields' => 'id,name',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
