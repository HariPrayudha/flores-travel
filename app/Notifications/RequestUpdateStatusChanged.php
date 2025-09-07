<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use App\Notifications\Channels\ExpoPushChannel;
use App\Notifications\Contracts\ExpoPushable;
use Illuminate\Support\Facades\Http;
use App\Models\PushToken;
use App\Models\User;

class RequestUpdateStatusChanged extends Notification implements ExpoPushable
{
    use Queueable;

    public function __construct(
        public \App\Models\RequestUpdateBarang $reqUpdate,
        public string $newStatus,
        public ?User $actor = null
    ) {}

    public function via(object $notifiable): array
    {
        return [DatabaseChannel::class, ExpoPushChannel::class];
    }

    public function toDatabase($notifiable): array
    {
        $ru = $this->reqUpdate->loadMissing(['barang', 'user']);

        return [
            'type'               => 'request_update_status',
            'request_update_id'  => $ru->id,
            'barang_id'          => $ru->barang_id,
            'kode_barang'        => optional($ru->barang)->kode_barang,
            'new_status'         => $this->newStatus,
            'updated_by'         => $this->actor?->name,
            'created_at'         => now()->toISOString(),
        ];
    }

    public function toExpoPush($notifiable)
    {
        $tokens = PushToken::where('user_id', $notifiable->id)->pluck('token');
        if ($tokens->isEmpty()) return null;

        $data  = $this->toDatabase($notifiable);
        $title = 'Status Request Diperbarui';
        $body  = "Pengajuan kamu {$this->newStatus} • {$data['kode_barang']}";

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
