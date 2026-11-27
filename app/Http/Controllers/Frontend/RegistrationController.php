<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\SendBibConfirmationMail;
use App\Jobs\SendBibWhatsApp;
use App\Models\WalkathonRegistration;
use App\Support\RegistrationForm;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(RegistrationForm::rules(), RegistrationForm::messages());

        $registration = WalkathonRegistration::create(RegistrationForm::toAttributes($data));

        // Email (bib + QR) and WhatsApp (bib + thank-you link) once the redirect has gone out.
        if ($registration->email) {
            SendBibConfirmationMail::dispatchAfterResponse($registration);
        }
        SendBibWhatsApp::dispatchAfterResponse($registration);

        return redirect()->route('frontend.thankyou', $registration->token);
    }

    public function thankYou(WalkathonRegistration $registration)
    {
        return Inertia::render('Frontend/ThankYou', [
            'registration' => [
                'firstName' => $registration->first_name,
                'fullName' => $registration->full_name,
                'email' => $registration->email,
                'bib' => $registration->bib_number,
                'qr' => $registration->qrDataUri(),
            ],
        ]);
    }
}
