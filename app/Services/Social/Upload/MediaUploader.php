<?php

namespace App\Services\Social\Upload;

use App\Models\SocialMedia;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaUploader
{
    private string $disk;

    public function __construct()
    {
        $this->disk = config('social.media_disk', 'social');
    }

    /**
     * Upload a media file for a team.
     */
    public function upload(Team $team, UploadedFile $file, ?string $platform = null): SocialMedia
    {
        $path = $file->store("{$team->id}", $this->disk);
        $type = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video';

        return SocialMedia::create([
            'team_id' => $team->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'type' => $type,
            'platform' => $platform,
        ]);
    }

    /**
     * Upload multiple media files for a team.
     *
     * @return Collection<int, SocialMedia>
     */
    public function uploadMultiple(Team $team, array $files, ?string $platform = null): Collection
    {
        $media = collect();

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $media->push($this->upload($team, $file, $platform));
            }
        }

        return $media;
    }

    /**
     * Delete a media file.
     */
    public function delete(SocialMedia $media): bool
    {
        Storage::disk($this->disk)->delete($media->file_path);

        return $media->delete();
    }

    /**
     * Delete multiple media files.
     */
    public function deleteMultiple(Collection $media): bool
    {
        foreach ($media as $item) {
            $this->delete($item);
        }

        return true;
    }

    /**
     * Get the storage disk name.
     */
    public function getDisk(): string
    {
        return $this->disk;
    }
}
