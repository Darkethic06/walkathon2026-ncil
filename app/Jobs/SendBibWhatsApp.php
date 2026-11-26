<?php

namespace App\Jobs;

use App\Models\WalkathonRegistration;
use App\Services\Wati;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends the bib number + thank-you page link on WhatsApp through WATI.
 * Like SendBibConfirmationMail it runs via dispatchAfterResponse(): no queue
 * worker, failures are logged and whatsapp_sent_at stays empty.
 * Does nothing until WATI_API_ENDPOINT and WATI_API_TOKEN are set.
 */
class SendBibWhatsApp
{
    use Dispatchable;

    public function __construct(public WalkathonRegistration $registration) {}

    public function handle(Wati $wati): void
    {
        if (! Wati::enabled()) {
            return;
        }

        $r = $this->registration;

        try {
            $wati->sendTemplate($r->whatsapp, config('services.wati.bib_template'), [
                'name' => $r->first_name,
                'bib_number' => $r->bib_number,
                'thank_you_link' => route('frontend.thankyou', $r->token),
            ]);
            $r->forceFill(['whatsapp_sent_at' => now()])->saveQuietly();
        } catch (Throwable $e) {
            Log::error('Walkathon bib WhatsApp failed', [
                'registration_id' => $r->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
