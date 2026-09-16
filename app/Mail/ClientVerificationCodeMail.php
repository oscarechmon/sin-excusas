<?php

namespace App\Mail;

use App\Models\ClientUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClientUser $user,
        public string $code,
        public int $minutes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->code} es tu código de verificación · Sin Excusas");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.client-verification-code');
    }
}
