<?php

// app/Notifications/RequestUpdateStatusChanged.php
namespace App\Notifications;

use App\Models\PushToken;
use App\Models\RequestUpdateBarang;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage; // optional
use Illuminate\Support\Facades\Http;

class RequestUpdateStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public RequestUpdateBarang $req,
        public string $newStatus,           // 'Disetujui' / 'Ditolak'
        public ?string $updatedByName = null
    ) {}

    public function via($notifiable): array
    {
        return [
            'database',
            \App\Notifications\Channels\ExpoPushChannel::class,
        ];
    }

    public function toDatabase($notifiable): array
    {
        $r = $this->req->loadMissing(['barang', 'user']);

        return [
            'type'               => 'request_update_status', // ← penting untuk FE
            'request_update_id'  => $r->id,
            'barang_id'          => $r->barang_id,
            'kode_barang'        => optional($r->barang)->kode_barang,
            'new_status'         => $this->newStatus,
            'updated_by'         => $this->updatedByName,
            'created_at'         => now()->toISOString(),
        ];
    }

    public function toExpoPush($notifiable)
    {
        $tokens = \App\Models\PushToken::where('user_id', $notifiable->id)->pluck('token');
        if ($tokens->isEmpty()) return null;

        $title = 'Status Request Update';
        $body  = "Pengajuan kamu {$this->newStatus}";
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
