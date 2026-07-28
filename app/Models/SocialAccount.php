<?php

namespace App\Models;

use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $platform
 * @property string $platform_user_id
 * @property string $name
 * @property string|null $page_id
 * @property string|null $instagram_account_id
 * @property string $access_token
 * @property Carbon|null $token_expires_at
 * @property string|null $profile_picture_url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, SocialPost> $posts
 */
#[Fillable(['team_id', 'platform', 'platform_user_id', 'name', 'page_id', 'instagram_account_id', 'access_token', 'token_expires_at', 'profile_picture_url', 'is_active'])]
class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    /**
     * Get the team that owns the social account.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get all posts for this social account.
     *
     * @return HasMany<SocialPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
