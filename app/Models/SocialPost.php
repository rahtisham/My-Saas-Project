<?php

namespace App\Models;

use Database\Factories\SocialPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property int $social_account_id
 * @property string|null $caption
 * @property string $platform
 * @property string $status
 * @property string $visibility
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $published_at
 * @property string|null $platform_post_id
 * @property array|null $platform_response
 * @property string|null $failure_reason
 * @property int $retry_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read User $user
 * @property-read SocialAccount $socialAccount
 * @property-read Collection<int, SocialMedia> $media
 * @property-read Collection<int, SocialCampaign> $campaigns
 */
#[Fillable(['team_id', 'user_id', 'social_account_id', 'caption', 'platform', 'status', 'visibility', 'scheduled_at', 'published_at', 'platform_post_id', 'platform_response', 'failure_reason', 'retry_count'])]
class SocialPost extends Model
{
    /** @use HasFactory<SocialPostFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the team that owns the post.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the post.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the social account for this post.
     *
     * @return BelongsTo<SocialAccount, $this>
     */
    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    /**
     * Get the media attached to this post.
     *
     * @return BelongsToMany<SocialMedia, $this>
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(SocialMedia::class, 'social_post_media', 'social_post_id', 'social_media_id')
            ->withTimestamps();
    }

    /**
     * Get the campaigns for this post.
     *
     * @return HasMany<SocialCampaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(SocialCampaign::class);
    }

    /**
     * Check if the post is scheduled and due.
     */
    public function isScheduledAndDue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at !== null && $this->scheduled_at->isPast();
    }

    /**
     * Check if the post can be published.
     */
    public function canBePublished(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'failed']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'platform_response' => 'array',
            'retry_count' => 'integer',
        ];
    }
}
