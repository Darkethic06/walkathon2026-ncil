<?php

use App\Jobs\SendBibConfirmationMail;
use App\Jobs\SendBibWhatsApp;
use App\Mail\BibConfirmationMail;
use App\Models\SupportTicket;
use App\Models\WalkathonRegistration;
use App\Support\RegistrationForm;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('renders the walkathon home page', function () {
    $this->get('/')->assertOk();
});

it('renders the privacy policy page', function () {
    $this->get('/privacy-policy')->assertOk();
});

it('stores a valid registration, gives it a bib and emails it after the response', function () {
    Bus::fake();

    $response = $this->post('/register', validRegistration());

    $registration = WalkathonRegistration::firstOrFail();
    expect($registration->bib_number)->toBe(WalkathonRegistration::bibFor($registration->id))
        ->toStartWith('4');
    $response->assertRedirect(route('frontend.thankyou', $registration->token));
    Bus::assertDispatchedAfterResponse(SendBibConfirmationMail::class);
});

it('shows the thank-you page with the bib and QR code', function () {
    $this->post('/register', validRegistration());
    $registration = WalkathonRegistration::firstOrFail();

    $this->get(route('frontend.thankyou', $registration->token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/ThankYou')
            ->where('registration.bib', $registration->bib_number)
            ->where('registration.qr', fn ($qr) => str_starts_with($qr, 'data:image/png;base64,')));
});

it('returns 404 for an unknown thank-you token', function () {
    $this->get('/thank-you/not-a-real-token')->assertNotFound();
});

it('builds bib numbers that start with 4 and have at least 4 digits', function () {
    expect(WalkathonRegistration::bibFor(1))->toBe('4001')
        ->and(WalkathonRegistration::bibFor(999))->toBe('4999')
        ->and(WalkathonRegistration::bibFor(1000))->toBe('41000')
        ->and(WalkathonRegistration::bibFor(12345))->toBe('412345');
});

it('sends the bib email with the QR attached', function () {
    Mail::fake();
    Bus::fake();
    $this->post('/register', validRegistration());
    $registration = WalkathonRegistration::firstOrFail();

    (new SendBibConfirmationMail($registration))->handle();

    Mail::assertSent(BibConfirmationMail::class, fn ($mail) => $mail->hasTo('asha@example.com'));

    (new BibConfirmationMail($registration))
        ->assertHasSubject("You're registered! Your bib number is {$registration->bib_number}")
        ->assertSeeInHtml($registration->bib_number)
        ->assertHasAttachment(
            Illuminate\Mail\Attachment::fromData(fn () => $registration->qrPng(), "walkathon-bib-{$registration->bib_number}-qr.png")
                ->withMime('image/png')
        );
    expect($registration->fresh()->mail_sent_at)->not->toBeNull();
});

it('rejects an invalid registration', function () {
    $this->post('/register', ['whatsapp' => '12345', 'privacy' => false])
        ->assertSessionHasErrors(['firstName', 'whatsapp', 'privacy']);

    expect(WalkathonRegistration::count())->toBe(0);
});

it('stores a support ticket', function () {
    $this->post('/support-ticket', [
        'name' => 'Rahul',
        'email' => 'rahul@example.com',
        'phone' => '8123456789',
        'message' => 'I did not receive my bib confirmation email.',
    ])->assertRedirect()->assertSessionHas('success');

    expect(SupportTicket::first()->message)->toBe('I did not receive my bib confirmation email.');
});

it('requires a message on a support ticket', function () {
    $this->post('/support-ticket', [
        'name' => 'Rahul',
        'email' => 'rahul@example.com',
        'phone' => '8123456789',
    ])->assertSessionHasErrors(['message']);

    expect(SupportTicket::count())->toBe(0);
});

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'firstName' => 'Asha',
        'lastName' => 'Sen',
        'gender' => 'Female',
        'email' => 'asha@example.com',
        'whatsapp' => '9876543210',
        'dob' => '1990-05-01',
        'bloodGroup' => 'O+',
        'address' => 'Sector V, Salt Lake, Kolkata',
        'emergency' => '9123456789',
        'medical' => 'None',
        'privacy' => true,
    ], $overrides);
}

it('rejects a second registration with the same email or WhatsApp number', function () {
    $this->post('/register', validRegistration())->assertSessionHasNoErrors();

    $this->post('/register', validRegistration(['whatsapp' => '9000000001']))
        ->assertSessionHasErrors(['email' => 'This email is already registered.']);

    $this->post('/register', validRegistration(['email' => 'other@example.com']))
        ->assertSessionHasErrors(['whatsapp' => 'This WhatsApp number is already registered.']);

    expect(WalkathonRegistration::count())->toBe(1);
});

it('accepts a registration without emergency contact or medical history', function () {
    Bus::fake();

    $this->post('/register', validRegistration(['emergency' => '', 'medical' => '']))
        ->assertSessionHasNoErrors();

    $registration = WalkathonRegistration::firstOrFail();
    expect($registration->emergency_contact)->toBeNull()
        ->and($registration->medical_history)->toBeNull();
});

it('rejects an emergency number that matches the WhatsApp number', function () {
    $this->post('/register', validRegistration(['emergency' => '9876543210']))
        ->assertSessionHasErrors(['emergency' => 'Emergency contact must be different from your WhatsApp number.']);
});

it('requires participants to be at least 5 years old', function () {
    $this->post('/register', validRegistration(['dob' => now()->subYears(5)->addDay()->toDateString()]))
        ->assertSessionHasErrors(['dob' => 'Participants must be at least 5 years old.']);

    $this->post('/register', validRegistration(['dob' => now()->subYears(5)->toDateString()]))
        ->assertSessionHasNoErrors();
});

it('sends the bib number and thank-you link on WhatsApp through WATI', function () {
    config(['services.wati.endpoint' => 'https://live-mt-server.wati.io/12345', 'services.wati.token' => 'Bearer secret']);
    Http::fake(['*' => Http::response(['result' => true])]);
    $registration = WalkathonRegistration::create(RegistrationForm::toAttributes(validRegistration()));
    SendBibWhatsApp::dispatchSync($registration);

    Http::assertSent(fn ($request) => $request->url() === 'https://live-mt-server.wati.io/12345/api/v1/sendTemplateMessage?whatsappNumber=919876543210'
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request['template_name'] === 'walkathon_bib_confirmation'
        && $request['parameters'] === [
            ['name' => 'name', 'value' => 'Asha'],
            ['name' => 'bib_number', 'value' => $registration->bib_number],
            ['name' => 'thank_you_link', 'value' => route('frontend.thankyou', $registration->token)],
        ]);
    expect($registration->fresh()->whatsapp_sent_at)->not->toBeNull();
});

it('logs a refused WhatsApp message and leaves it unsent', function () {
    config(['services.wati.endpoint' => 'https://wati.test', 'services.wati.token' => 'secret']);
    Http::fake(['*' => Http::response(['result' => false, 'info' => 'Template not approved'])]);
    $registration = WalkathonRegistration::create(RegistrationForm::toAttributes(validRegistration()));
    SendBibWhatsApp::dispatchSync($registration);

    expect($registration->fresh()->whatsapp_sent_at)->toBeNull();
});

it('skips WhatsApp when WATI is not configured', function () {
    config(['services.wati.endpoint' => null, 'services.wati.token' => null]);
    Http::fake();
    SendBibWhatsApp::dispatchSync(WalkathonRegistration::create(RegistrationForm::toAttributes(validRegistration())));

    Http::assertNothingSent();
});

it('sends through the WATI v3 API when the endpoint is a v3 one', function () {
    config(['services.wati.endpoint' => 'https://live-mt-server.wati.io/api/ext/v3', 'services.wati.token' => 'secret']);
    Http::fake(['*' => Http::response(['success' => true, 'broadcast_id' => 'b1', 'recipients' => [
        ['local_message_id' => 'm1', 'phone_number' => '919876543210', 'errors' => []],
    ]])]);
    $registration = WalkathonRegistration::create(RegistrationForm::toAttributes(validRegistration()));

    SendBibWhatsApp::dispatchSync($registration);

    Http::assertSent(fn ($request) => $request->url() === 'https://live-mt-server.wati.io/api/ext/v3/messageTemplates/send'
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request['template_name'] === 'walkathon_bib_confirmation'
        && $request['recipients'][0]['phone_number'] === '919876543210'
        && $request['recipients'][0]['custom_params'][1] === ['name' => 'bib_number', 'value' => $registration->bib_number]);
    expect($registration->fresh()->whatsapp_sent_at)->not->toBeNull();
});

it('treats a v3 recipient error as a failed WhatsApp send', function () {
    config(['services.wati.endpoint' => 'https://live-mt-server.wati.io/api/ext/v3', 'services.wati.token' => 'secret']);
    Http::fake(['*' => Http::response(['success' => true, 'recipients' => [
        ['phone_number' => '919876543210', 'errors' => ['Invalid WhatsApp number']],
    ]])]);
    $registration = WalkathonRegistration::create(RegistrationForm::toAttributes(validRegistration()));

    SendBibWhatsApp::dispatchSync($registration);

    expect($registration->fresh()->whatsapp_sent_at)->toBeNull();
});
