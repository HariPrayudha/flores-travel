<?php

namespace App\Notifications;

use App\Models\PushToken;
use App\Models\RequestUpdateBarang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Support\Facades\Http;
use App\Notifications\Channels\ExpoPushChannel;

class RequestUpdateBarangStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public RequestUpdateBarang $reqUpdate,
        public string $status
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class, ExpoPushChannel::class];
    }

    public function toDatabase($notifiable)
    {
        $ru = $this->reqUpdate->loadMissing(['barang', 'user']);
        return [
            'type'              => 'request_update_status',
            'request_update_id' => $ru->id,
            'barang_id'         => $ru->barang_id,
            'kode_barang'       => optional($ru->barang)->kode_barang,
            'status_update'     => $this->status,
            'updated_at'        => now()->toISOString(),
        ];
    }

    public function toExpoPush($notifiable)
    {
        $tokens = PushToken::where('user_id', $notifiable->id)->pluck('token');
        if ($tokens->isEmpty()) return null;

        $ru   = $this->reqUpdate->loadMissing('barang');
        $title = 'Status Request Update';
        $body  = sprintf(
            'Request %s • %s',
            $this->status,
            optional($ru->barang)->kode_barang ?: '—'
        );
        $data = $this->toDatabase($notifiable);

        $messages = [];
        foreach ($tokens as $token) {
            $messages[] = [
                'to'       => $token,
                'title'    => $title,
                'body'     => $body,
                'data'     => $data,
                'sound'    => 'default',
                'priority' => 'high',
            ];
        }

        return Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $messages);
    }
}
