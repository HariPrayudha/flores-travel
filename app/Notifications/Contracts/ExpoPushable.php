<?php

namespace App\Notifications\Contracts;

interface ExpoPushable
{
    /**
     * Kirim payload ke Expo Push API.
     * Return Illuminate\Http\Client\Response|null
     *
     * @param  mixed  $notifiable
     */
    public function toExpoPush($notifiable);
}
