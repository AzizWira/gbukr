<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $status) {}

    public function via(object $notifiable): array
    {
        return str_ends_with((string) $notifiable->email, '@placeholder.local') ? ['database'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Status barang diperbarui — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Status barang diperbarui',
            lines: [
                'Order ' . $this->order->order_number . ' sekarang berstatus: ' . OrderStatusService::label($this->status) . '.',
            ],
            actionText: 'Lihat Order',
            actionUrl: route('customer.orders.show', $this->order),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Status barang diperbarui',
            'order_id' => $this->order->id,
            'status' => $this->status,
            'label' => OrderStatusService::label($this->status),
        ];
    }
}
