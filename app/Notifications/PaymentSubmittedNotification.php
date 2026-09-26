<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Bukti pembayaran baru — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Ada bukti pembayaran baru',
            lines: [
                'Ada bukti pembayaran baru sebesar Rp' . number_format($this->payment->amount, 0, ',', '.') . '.',
                'Cocokkan bukti dengan mutasi sebelum melakukan verifikasi.',
            ],
            actionText: 'Periksa Pembayaran',
            actionUrl: route('owner.payments.show', $this->payment),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return ['payment_id' => $this->payment->id, 'message' => 'Bukti pembayaran baru menunggu verifikasi.'];
    }
}
