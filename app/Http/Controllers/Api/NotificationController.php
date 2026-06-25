<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()
            ->unreadNotifications()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    public function preferences(Request $request)
    {
        return response()->json(
            $request->user()->notification_preferences ?? [
                'email' => true,
                'sms' => false,
                'push' => true,
            ]
        );
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'email' => 'boolean',
            'sms' => 'boolean',
            'push' => 'boolean',
        ]);

        $request->user()->update([
            'notification_preferences' => $validated,
        ]);

        return response()->json($validated);
    }
}
