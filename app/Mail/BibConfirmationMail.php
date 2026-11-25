<?php

namespace App\Mail;

use App\Models\WalkathonRegistration;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BibConfirmationMail extends Mailable
{
    public function __construct(public WalkathonRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered! Your bib number is {$this->registration->bib_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.bib-confirmation',
            with: [
                'registration' => $this->registration,
                'qrPng' => $this->registration->qrPng(),
                'thankYouUrl' => route('frontend.thankyou', $this->registration->token),
            ],
        );
    }

    public function attachments(): array
    {
        $bib = $this->registration->bib_number;

        return [
            Attachment::fromData(fn () => $this->registration->qrPng(), "walkathon-bib-{$bib}-qr.png")
                ->withMime('image/png'),
        ];
    }
}
