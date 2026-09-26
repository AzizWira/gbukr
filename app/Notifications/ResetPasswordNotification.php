<?php

namespace App\Notifications;

use App\Mail\BrandedNotificationMail;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): BrandedNotificationMail
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new BrandedNotificationMail(
            subjectLine: 'Reset password — GBUKPOP x KRJASTIP',
            recipientName: $notifiable->name,
            headline: 'Buat password baru',
            lines: [
                'Kami menerima permintaan untuk membuat password baru pada akunmu.',
                'Link ini hanya dapat digunakan untuk akun yang menerima email ini dan akan tidak berlaku setelah password berhasil diubah.',
            ],
            actionText: 'Buat Password Baru',
            actionUrl: $url,
            footnote: 'Link berlaku selama ' . $minutes . ' menit. Jika kamu tidak meminta reset password, abaikan email ini.',
        ))->to($notifiable->email, $notifiable->name);
    }
}
