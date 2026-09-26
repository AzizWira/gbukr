<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return str_ends_with((string) $notifiable->email, '@placeholder.local') ? ['database'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        $amount = 'Rp' . number_format($this->invoice->amount, 0, ',', '.');
        $deadline = $this->invoice->deadline_at?->translatedFormat('d F Y, H.i');
        $lines = ['Tagihan baru ' . $this->invoice->invoice_number . ' tersedia sebesar ' . $amount . '.'];
        if ($deadline) {
            $lines[] = 'Batas pembayaran: ' . $deadline . '.';
        }

        return (new BrandedNotificationMail(
            subjectLine: 'Tagihan baru — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Ada tagihan baru',
            lines: $lines,
            actionText: 'Lihat Tagihan',
            actionUrl: route('customer.invoices.show', $this->invoice),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Tagihan baru', 'invoice_id' => $this->invoice->id, 'amount' => $this->invoice->amount];
    }
}
