<?php

namespace App\Models;

use Database\Factories\SocialMediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $team_id
 * @property string $file_path
 * @property string $file_name
 * @property string $mime_type
 * @property int $file_size
 * @property string $type
 * @property string|null $platform
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read string $url
 */
#[Fillable(['team_id', 'file_path', 'file_name', 'mime_type', 'file_size', 'type', 'platform'])]
class SocialMedia extends Model
{
    /** @use HasFactory<SocialMediaFactory> */
    use HasFactory;

    /**
     * Get the team that owns the media.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the public URL for the media file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('social')->url($this->file_path);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }
}
