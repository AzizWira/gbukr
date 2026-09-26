<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class UnclaimedOwnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Barang masuk Unclaimed — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Barang masuk status Unclaimed',
            lines: [
                $this->order->order_number . ' sudah melewati 3 bulan sejak Arrived GBU/KRJASTIP dan ditandai Unclaimed.',
            ],
            actionText: 'Periksa Order',
            actionUrl: route('owner.orders.show', $this->order),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return ['order_id' => $this->order->id, 'message' => 'Order masuk status Unclaimed.'];
    }
}
