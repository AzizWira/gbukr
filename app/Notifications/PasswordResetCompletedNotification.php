<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PasswordResetCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Password berhasil diubah — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Password berhasil diubah',
            lines: [
                'Password akun GBUKR kamu sudah berhasil diperbarui.',
            ],
            actionText: 'Masuk ke GBUKR',
            actionUrl: route('login'),
            footnote: 'Jika bukan kamu yang melakukan perubahan ini, segera hubungi pengelola GBUKPOP x KRJASTIP.',
        ))->to($notifiable->email, $notifiable->name);
    }
}
