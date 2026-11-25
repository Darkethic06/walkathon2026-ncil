<?php

use App\Jobs\SendBibConfirmationMail;
use App\Mail\BibConfirmationMail;
use App\Models\SupportTicket;
use App\Models\WalkathonRegistration;
use Illuminate\Support\Facades\Bus;
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
    ])->assertRedirect()->assertSessionHas('success');

    expect(SupportTicket::count())->toBe(1);
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
