<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function savePushToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'platform' => 'required|in:ios,android'
        ]);

        PushToken::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'platform' => $validated['platform']
            ],
            ['token' => $validated['token']]
        );

        return response()->json(['success' => true]);
    }

    // public function sendExpoPushNotification($tokens, $title, $body, $data = [])
    // {
    //     $messages = [];

    //     foreach ($tokens as $token) {
    //         $messages[] = [
    //             'to' => $token,
    //             'title' => $title,
    //             'body' => $body,
    //             'data' => $data,
    //             'sound' => 'default',
    //             'priority' => 'high',
    //         ];
    //     }

    //     return Http::withHeaders([
    //         'Accept' => 'application/json',
    //         'Accept-Encoding' => 'gzip, deflate',
    //         'Content-Type' => 'application/json',
    //     ])->post('https://exp.host/--/api/v2/push/send', $messages);
    // }
}
