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

class SyncCampaignInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SocialCampaign $campaign,
    ) {
        $this->queue = config('social.publishing.queue', 'default');
    }

    /**
     * Execute the job.
     */
    public function handle(CampaignManager $manager): void
    {
        if (! $this->campaign->isActive()) {
            return;
        }

        $manager->getInsights($this->campaign);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign insights sync failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
