<?php

namespace App\Models;

use Database\Factories\SocialCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property int|null $social_post_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $platform
 * @property float|null $budget
 * @property float $spent
 * @property string|null $objective
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $platform_campaign_id
 * @property array|null $targeting
 * @property array|null $insights
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read SocialPost|null $socialPost
 */
#[Fillable(['team_id', 'user_id', 'social_post_id', 'name', 'description', 'status', 'platform', 'budget', 'spent', 'objective', 'start_date', 'end_date', 'platform_campaign_id', 'targeting', 'insights', 'failure_reason'])]
class SocialCampaign extends Model
{
    /** @use HasFactory<SocialCampaignFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the team that owns the campaign.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the campaign.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post linked to this campaign.
     *
     * @return BelongsTo<SocialPost, $this>
     */
    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    /**
     * Check if the campaign is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the campaign has budget remaining.
     */
    public function hasBudgetRemaining(): bool
    {
        return $this->budget !== null && $this->spent < $this->budget;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'float',
            'spent' => 'float',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'targeting' => 'array',
            'insights' => 'array',
        ];
    }
}
