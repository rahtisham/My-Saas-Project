<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialNotification;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SocialNotificationController extends Controller
{
    /**
     * Display the user's social notifications.
     */
    public function index(Team $currentTeam): Response
    {
        $notifications = SocialNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (SocialNotification $notification) => $this->formatNotification($notification));

        $unreadCount = SocialNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return Inertia::render('social/notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Team $currentTeam, SocialNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification marked as read.')]);

        return to_route('social.notifications.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Team $currentTeam): RedirectResponse
    {
        SocialNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All notifications marked as read.')]);

        return to_route('social.notifications.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Format a notification for the frontend.
     *
     * @return array{id: int, type: string, title: string, message: string, data: array|null, readAt: string|null, createdAt: string}
     */
    private function formatNotification(SocialNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data,
            'readAt' => $notification->read_at?->toIso8601String(),
            'createdAt' => $notification->created_at->toIso8601String(),
        ];
    }
}
