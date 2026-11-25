<?php

namespace App\Jobs;

use App\Mail\BibConfirmationMail;
use App\Models\WalkathonRegistration;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the bib + QR email. Dispatched with dispatchAfterResponse(), so it runs
 * in the same PHP process right after the thank-you redirect is sent: the
 * walker doesn't wait for SMTP, and no queue worker is needed.
 * A failed send is logged and leaves mail_sent_at empty, so it can be resent.
 */
class SendBibConfirmationMail
{
    use Dispatchable;

    public function __construct(public WalkathonRegistration $registration) {}

    public function handle(): void
    {
        try {
            Mail::to($this->registration->email)->send(new BibConfirmationMail($this->registration));
            $this->registration->forceFill(['mail_sent_at' => now()])->saveQuietly();
        } catch (Throwable $e) {
            Log::error('Walkathon bib email failed', [
                'registration_id' => $this->registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
