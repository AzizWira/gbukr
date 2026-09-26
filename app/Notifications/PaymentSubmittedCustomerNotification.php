<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentSubmittedCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return str_ends_with((string) $notifiable->email, '@placeholder.local') ? ['database'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Bukti pembayaran diterima — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Bukti pembayaran sudah diterima',
            lines: [
                'Bukti untuk ' . $this->payment->payment_number . ' sudah kami terima dan sedang menunggu verifikasi mutasi.',
                'Total pembayaran: Rp' . number_format($this->payment->amount, 0, ',', '.') . '.',
            ],
            actionText: 'Lihat Tagihan',
            actionUrl: route('customer.invoices.index'),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return ['payment_id' => $this->payment->id, 'message' => 'Bukti pembayaran sudah dikirim dan menunggu verifikasi.'];
    }
}
