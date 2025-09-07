<?php

namespace App\Notifications;

use App\Models\PushToken;
use App\Models\RequestUpdateBarang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class RequestUpdateBarangCreated extends Notification
{
    use Queueable;

    public function __construct(public RequestUpdateBarang $reqUpdate)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database', 'expo-push'];
    }

    public function toDatabase($notifiable)
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

        if ($tokens->isEmpty()) {
            return null;
        }

        $title = 'Request Update Barang';
        $body = "Ada request update dari {$this->reqUpdate->user->name}";
        $data = $this->toDatabase($notifiable);

        $messages = [];
        foreach ($tokens as $token) {
            $messages[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
            ];
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $messages);

        return $response;
    }
}
