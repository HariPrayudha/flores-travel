<?php

namespace App\Notifications;

use App\Models\RequestUpdateBarang;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestUpdateBarangCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public RequestUpdateBarang $reqUpdate)
    {
        //
    }

    public function toDatabase($notifiable)
    {
        $ru = $this->reqUpdate->loadMissing(['barang','user']);
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


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
