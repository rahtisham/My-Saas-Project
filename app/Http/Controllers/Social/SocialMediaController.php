<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\UploadMediaRequest;
use App\Models\SocialMedia;
use App\Models\Team;
use App\Services\Social\Upload\MediaUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SocialMediaController extends Controller
{
    public function __construct(
        private MediaUploader $uploader,
    ) {}

    /**
     * Display a listing of the team's social media.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [SocialMedia::class, $currentTeam]);

        $media = $currentTeam->socialMedia()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialMedia $item) => $this->formatMedia($item));

        return Inertia::render('social/media/Index', [
            'media' => $media,
        ]);
    }

    /**
     * Show the upload form.
     */
    public function create(Team $currentTeam): Response
    {
        Gate::authorize('create', [SocialMedia::class, $currentTeam]);

        return Inertia::render('social/media/Upload');
    }

    /**
     * Upload media files.
     */
    public function store(UploadMediaRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialMedia::class, $currentTeam]);

        $files = $request->file('files');
        $this->uploader->uploadMultiple($currentTeam, $files);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media uploaded successfully.')]);

        return to_route('social.media.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Team $currentTeam, SocialMedia $media): RedirectResponse
    {
        Gate::authorize('delete', $media);

        $this->uploader->delete($media);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Media deleted.')]);

        return to_route('social.media.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Format media for the frontend.
     *
     * @return array{id: int, fileName: string, mimeType: string, type: string, url: string, createdAt: string}
     */
    private function formatMedia(SocialMedia $media): array
    {
        return [
            'id' => $media->id,
            'fileName' => $media->file_name,
            'mimeType' => $media->mime_type,
            'type' => $media->type,
            'url' => $media->url,
            'createdAt' => $media->created_at->toIso8601String(),
        ];
    }
}
