<?php

namespace App\Services\Social\Facebook;

use App\Models\SocialAccount;
use App\Models\SocialCampaign;
use App\Models\SocialNotification;
use Illuminate\Support\Facades\Log;

class CampaignManager
{
    public function __construct(
        private FacebookService $facebook,
    ) {}

    /**
     * Create a new marketing campaign.
     */
    public function create(SocialCampaign $campaign): SocialCampaign
    {
        $account = $campaign->socialPost?->socialAccount;

        if (! $account) {
            throw new \RuntimeException('Campaign must be linked to a post with a social account');
        }

        if ($account->isTokenExpired()) {
            throw new \RuntimeException('Social account access token has expired');
        }

        try {
            $result = $this->createFacebookCampaign($campaign, $account);

            $campaign->update([
                'status' => 'active',
                'platform_campaign_id' => $result['id'] ?? null,
            ]);

            $this->notifyUser($campaign->user_id, 'campaign_started', 'Campaign Started', "Your campaign \"{$campaign->name}\" has been started.");

            return $campaign->fresh();
        } catch (\Exception $e) {
            Log::error('Campaign creation failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            $campaign->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            $this->notifyUser($campaign->user_id, 'campaign_failed', 'Campaign Failed', "Your campaign \"{$campaign->name}\" failed to start: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Pause an active campaign.
     */
    public function pause(SocialCampaign $campaign): SocialCampaign
    {
        if (! $campaign->platform_campaign_id) {
            throw new \RuntimeException('Campaign has no linked platform campaign');
        }

        $account = $this->getAccountForCampaign($campaign);

        try {
            $this->updateCampaignStatus($campaign->platform_campaign_id, 'PAUSED', $account);

            $campaign->update(['status' => 'paused']);

            return $campaign->fresh();
        } catch (\Exception $e) {
            Log::error('Campaign pause failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(SocialCampaign $campaign): SocialCampaign
    {
        if (! $campaign->platform_campaign_id) {
            throw new \RuntimeException('Campaign has no linked platform campaign');
        }

        $account = $this->getAccountForCampaign($campaign);

        try {
            $this->updateCampaignStatus($campaign->platform_campaign_id, 'ACTIVE', $account);

            $campaign->update(['status' => 'active']);

            return $campaign->fresh();
        } catch (\Exception $e) {
            Log::error('Campaign resume failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get campaign insights from the platform.
     *
     * @return array<string, mixed>
     */
    public function getInsights(SocialCampaign $campaign): array
    {
        if (! $campaign->platform_campaign_id) {
            return [];
        }

        $account = $this->getAccountForCampaign($campaign);

        try {
            $response = $this->facebook->get("{$campaign->platform_campaign_id}/insights", $account, [
                'fields' => 'impressions,reach,clicks,likes,comments,shares,engagement',
                'access_token' => $account->access_token,
            ]);

            $insights = $this->formatInsights($response);

            $campaign->update(['insights' => $insights]);

            return $insights;
        } catch (\Exception $e) {
            Log::error('Campaign insights fetch failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create a Facebook Ads campaign.
     *
     * @return array<string, mixed>
     */
    private function createFacebookCampaign(SocialCampaign $campaign, SocialAccount $account): array
    {
        $adAccountId = $account->page_id;

        $result = $this->facebook->post("act_{$adAccountId}/campaigns", $account, [
            'name' => $campaign->name,
            'objective' => $this->mapObjective($campaign->objective),
            'status' => 'ACTIVE',
            'special_ad_categories' => [],
        ]);

        if ($campaign->budget && $campaign->start_date && $campaign->end_date) {
            $this->createAdSet($result['id'], $campaign, $account);
        }

        return $result;
    }

    /**
     * Create an ad set for a campaign.
     *
     * @return array<string, mixed>
     */
    private function createAdSet(string $campaignId, SocialCampaign $campaign, SocialAccount $account): array
    {
        $adAccountId = $account->page_id;

        return $this->facebook->post("act_{$adAccountId}/adsets", $account, [
            'name' => "{$campaign->name} - Ad Set",
            'campaign_id' => $campaignId,
            'daily_budget' => (string) ($campaign->budget * 100),
            'billing_event' => 'IMPRESSIONS',
            'optimization_goal' => 'REACH',
            'start_time' => $campaign->start_date?->format('Y-m-d\TH:i:s'),
            'end_time' => $campaign->end_date?->format('Y-m-d\TH:i:s'),
            'targeting' => $campaign->targeting ?? $this->getDefaultTargeting(),
        ]);
    }

    /**
     * Update campaign status on the platform.
     */
    private function updateCampaignStatus(string $campaignId, string $status, SocialAccount $account): void
    {
        $this->facebook->post("{$campaignId}", $account, [
            'status' => $status,
        ]);
    }

    /**
     * Map campaign objective to Facebook Ads API objective.
     */
    private function mapObjective(?string $objective): string
    {
        return match ($objective) {
            'Engagement' => 'ENGAGEMENT',
            'Traffic' => 'OUTCOME_TRAFFIC',
            'Conversions' => 'OUTCOME_SALES',
            'Brand Awareness' => 'OUTCOME_AWARENESS',
            'Reach' => 'OUTCOME_AWARENESS',
            default => 'OUTCOME_TRAFFIC',
        };
    }

    /**
     * Get default targeting for campaigns.
     *
     * @return array<string, mixed>
     */
    private function getDefaultTargeting(): array
    {
        return [
            'age_min' => 18,
            'age_max' => 65,
            'genders' => [0, 1, 2],
            'geo_locations' => [
                'countries' => ['US'],
            ],
        ];
    }

    /**
     * Format insights data from API response.
     *
     * @return array<string, mixed>
     */
    private function formatInsights(array $response): array
    {
        $data = $response['data'] ?? [];

        $formatted = [];

        foreach ($data as $metric) {
            $formatted[$metric['name']] = $metric['values'][0]['value'] ?? 0;
        }

        return $formatted;
    }

    /**
     * Get the social account for a campaign.
     */
    private function getAccountForCampaign(SocialCampaign $campaign): SocialAccount
    {
        $account = $campaign->socialPost?->socialAccount;

        if (! $account) {
            throw new \RuntimeException('No social account found for campaign');
        }

        return $account;
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
