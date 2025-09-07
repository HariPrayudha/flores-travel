<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Notifications\Contracts\ExpoPushable;

class ExpoPushChannel
{
    public function send($notifiable, Notification $notification)
    {
        if ($notification instanceof ExpoPushable) {
            return $notification->toExpoPush($notifiable);
        }
        return null;
    }
}
