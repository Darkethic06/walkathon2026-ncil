<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Resend the bib WhatsApp to everyone who hasn't got it yet (e.g. registered before the
// template was approved, or while WATI was misconfigured). Safe to re-run.
Artisan::command('walkathon:resend-whatsapp {--id=* : Only these registration ids} {--dry-run}', function () {
    if (! \App\Services\Wati::enabled()) {
        $this->error('WATI is not configured: set WATI_API_ENDPOINT / WATI_API_TOKEN, then run php artisan config:cache.');

        return 1;
    }

    $query = \App\Models\WalkathonRegistration::whereNull('whatsapp_sent_at')
        ->when($this->option('id'), fn ($q, $ids) => $q->whereIn('id', $ids));

    $this->info($query->count().' registration(s) without a WhatsApp.');
    if ($this->option('dry-run')) {
        return 0;
    }

    $query->lazyById()->each(function ($r) {
        \App\Jobs\SendBibWhatsApp::dispatchSync($r);
        $this->line("#{$r->id} ".($r->refresh()->whatsapp_sent_at ? 'sent' : 'FAILED (see laravel.log)'));
    });

    return 0;
})->purpose('Send the bib WhatsApp to registrations that have not received it');
