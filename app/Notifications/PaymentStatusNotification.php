<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return str_ends_with((string) $notifiable->email, '@placeholder.local') ? ['database'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        $approved = $this->payment->status === 'approved';
        $lines = [
            $approved
                ? 'Pembayaran ' . $this->payment->payment_number . ' sudah diverifikasi.'
                : 'Bukti pembayaran ' . $this->payment->payment_number . ' belum dapat diverifikasi.',
        ];

        if (!$approved && $this->payment->rejection_reason) {
            $lines[] = 'Keterangan: ' . $this->payment->rejection_reason;
        }

        return (new BrandedNotificationMail(
            subjectLine: $approved ? 'Pembayaran sudah diverifikasi — GBUKPOP x KRJASTIP' : 'Bukti pembayaran perlu diperbaiki — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: $approved ? 'Pembayaran sudah diverifikasi' : 'Bukti pembayaran perlu diperbaiki',
            lines: $lines,
            actionText: 'Lihat Tagihan',
            actionUrl: route('customer.invoices.index'),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->payment->status === 'approved' ? 'Pembayaran diverifikasi' : 'Pembayaran ditolak',
            'payment_id' => $this->payment->id,
            'status' => $this->payment->status,
        ];
    }
}
