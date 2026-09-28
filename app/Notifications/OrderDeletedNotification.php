<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public array $snapshot, public string $reason) {}
    public function via(object $notifiable): array { return ['mail','database']; }
    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine:'Order dihapus — GBUKPOP x KRJASTIP',
            recipientName:$notifiable->name,
            headline:'Order telah dihapus',
            lines:[
                'Order '.($this->snapshot['order_number'] ?? '-').' telah dihapus dari data operasional.',
                'Alasan: '.$this->reason,
                'Jejak audit transaksi tetap disimpan secara internal untuk keamanan data.',
            ],
            actionText:'Buka Dashboard',
            actionUrl:route('customer.dashboard'),
        ))->to($notifiable->email,$notifiable->name);
    }
    public function toArray(object $notifiable): array
    {
        return ['title'=>'Order telah dihapus','order_number'=>$this->snapshot['order_number'] ?? null,'reason'=>$this->reason];
    }
}
