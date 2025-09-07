<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Notifications\RequestUpdateBarangCreated;

class ExpoPushChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if ($notification instanceof RequestUpdateBarangCreated) {
            return $notification->toExpoPush($notifiable);
        }
    }
}
