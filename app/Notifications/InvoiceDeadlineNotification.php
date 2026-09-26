<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return str_ends_with((string) $notifiable->email, '@placeholder.local') ? ['database'] : ['mail', 'database'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Tagihan melewati deadline — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Tagihan melewati deadline',
            lines: [
                'Tagihan ' . $this->invoice->invoice_number . ' sudah melewati batas pembayaran.',
                'Sisa tagihan saat ini: Rp' . number_format($this->invoice->outstanding(), 0, ',', '.') . '.',
            ],
            actionText: 'Lihat Tagihan',
            actionUrl: route('customer.invoices.show', $this->invoice),
        ))->to($notifiable->email, $notifiable->name);
    }

    public function toArray(object $notifiable): array
    {
        return ['invoice_id' => $this->invoice->id, 'message' => 'Tagihan sudah melewati deadline.'];
    }
}
