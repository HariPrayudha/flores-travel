<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $user->unreadNotifications()->count()],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $onlyUnread = filter_var($request->query('only_unread'), FILTER_VALIDATE_BOOL);
        $perPage = (int) ($request->query('per_page', 20));

        $query = $onlyUnread ? $user->unreadNotifications() : $user->notifications();
        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $notifications,
        ]);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notif = $user->notifications()->where('id', $id)->first();

        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        if (is_null($notif->read_at)) {
            $notif->markAsRead();
        }

        return response()->json(['success' => true, 'data' => $notif]);
    }
}
