<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use Illuminate\Auth\Notifications\VerifyEmail;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): BrandedNotificationMail
    {
        return (new BrandedNotificationMail(
            subjectLine: 'Verifikasi email — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Verifikasi emailmu',
            lines: [
                'Verifikasi email ini agar kamu dapat menggunakan dashboard, checkout, melihat tagihan, dan mengelola order dengan aman.',
                'Link verifikasi hanya dapat digunakan sampai akun berhasil diverifikasi.',
            ],
            actionText: 'Verifikasi Email',
            actionUrl: $this->verificationUrl($notifiable),
            footnote: 'Jika kamu tidak membuat akun GBUKR, abaikan email ini.',
        ))->to($notifiable->email, $notifiable->name);
    }
}
