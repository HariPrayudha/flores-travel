<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // terbaru duluan, limit sederhana
        $list = $request->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->take((int)($request->get('limit', 50)))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $list,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    // public function markAsRead(Request $request, $id)
    // {
    //     $user = $request->user();
    //     $notif = $user->notifications()->where('id', $id)->first();

    //     if (!$notif) {
    //         return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
    //     }

    //     if (is_null($notif->read_at)) {
    //         $notif->markAsRead();
    //     }

    //     return response()->json(['success' => true, 'data' => $notif]);
    // }
}
