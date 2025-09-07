<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use App\Notifications\Channels\ExpoPushChannel;
use App\Notifications\Contracts\ExpoPushable;
use Illuminate\Support\Facades\Http;
use App\Models\PushToken;

class RequestUpdateBarangCreated extends Notification implements ExpoPushable
{
    use Queueable;

    public function __construct(public \App\Models\RequestUpdateBarang $reqUpdate) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class, ExpoPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $ru = $this->reqUpdate->loadMissing(['barang', 'user']);
        return [
            'type'              => 'request_update_barang',
            'request_update_id' => $ru->id,
            'barang_id'         => $ru->barang_id,
            'kode_barang'       => optional($ru->barang)->kode_barang,
            'requested_by'      => optional($ru->user)->name,
            'requested_by_id'   => $ru->user_id,
            'status_bayar'      => $ru->status_bayar,
            'created_at'        => now()->toISOString(),
        ];
    }

    public function toExpoPush($notifiable)
    {
        $tokens = PushToken::where('user_id', $notifiable->id)->pluck('token');
        if ($tokens->isEmpty()) return null;

        $ru    = $this->reqUpdate->loadMissing(['barang', 'user']);
        $title = 'Request Update Barang';
        $body  = "Ada request update barang nih, dari {$ru->user->name}";
        $data  = $this->toDatabase($notifiable);

        $messages = $tokens->map(fn($t) => [
            'to'       => $t,
            'title'    => $title,
            'body'     => $body,
            'data'     => $data,
            'sound'    => 'default',
            'priority' => 'high',
        ])->values()->all();

        return Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $messages);
    }
}
