<?php

namespace App\Jobs;

use App\Models\SocialCampaign;
use App\Services\Social\Facebook\CampaignManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllCampaignInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(CampaignManager $manager): void
    {
        $activeCampaigns = SocialCampaign::where('status', 'active')
            ->whereNotNull('platform_campaign_id')
            ->get();

        foreach ($activeCampaigns as $campaign) {
            try {
                SyncCampaignInsights::dispatch($campaign);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch campaign sync', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
