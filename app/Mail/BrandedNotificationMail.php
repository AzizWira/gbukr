<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrandedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $recipientName,
        public string $headline,
        public array $lines = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public string $accent = 'blue',
        public ?string $footnote = null,
    ) {}

    public function build(): static
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.branded');
    }
}
