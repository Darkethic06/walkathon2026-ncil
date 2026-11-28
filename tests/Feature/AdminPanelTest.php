<?php

use App\Models\User;
use App\Models\WalkathonRegistration;
use App\Support\Roles;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = User::create(['first_name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'secret123', 'active' => 1]);
    $this->admin->assignRole(Roles::SUPER_ADMIN);

    $this->volunteer = User::create(['first_name' => 'Vol', 'last_name' => 'One', 'email' => 'vol@test.com', 'password' => 'secret123', 'active' => 1]);
    $this->volunteer->assignRole(Roles::SUB_USER);

    $this->walker = WalkathonRegistration::create([
        'first_name' => 'Asha', 'last_name' => 'Sen', 'gender' => 'Female',
        'email' => 'asha@example.com', 'whatsapp' => '9876543210', 'dob' => '1990-05-01',
        'blood_group' => 'O+', 'address' => 'Salt Lake, Kolkata', 'emergency_contact' => '9123456789',
        'medical_history' => 'Asthma', 'privacy_accepted_at' => now(),
    ])->fresh();
});

it('lets a sub-user log in', function () {
    $this->post(route('admin.login.post'), ['email' => 'vol@test.com', 'password' => 'secret123'])
        ->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($this->volunteer);
});

it('shows sub-users only the check-in fields', function () {
    $this->actingAs($this->volunteer)->get(route('admin.registrations'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('registrations.data.0.bib', $this->walker->bib_number)
            ->missing('registrations.data.0.email')
            ->missing('registrations.data.0.medical_history')
            ->missing('registrations.data.0.address'));
});

it('shows admins every field', function () {
    $this->actingAs($this->admin)->get(route('admin.registrations.show', $this->walker))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('registration.email', 'asha@example.com')
            ->where('registration.medical_history', 'Asthma'));
});

it('finds a walker from the scanned QR text or a typed bib', function () {
    $this->actingAs($this->volunteer);

    $this->get(route('admin.scan', ['code' => $this->walker->qrPayload()]))
        ->assertInertia(fn ($page) => $page->where('registration.bib', $this->walker->bib_number));

    $this->get(route('admin.scan', ['code' => $this->walker->bib_number]))
        ->assertInertia(fn ($page) => $page->where('registration.name', 'Asha Sen'));

    $this->get(route('admin.scan', ['code' => 'random text']))
        ->assertInertia(fn ($page) => $page->where('registration', null));
});

it('lets a sub-user mark a walker as attended once', function () {
    $this->actingAs($this->volunteer)
        ->post(route('admin.registrations.attend', $this->walker))
        ->assertSessionHas('success');

    $this->walker->refresh();
    expect($this->walker->attended_at)->not->toBeNull()
        ->and($this->walker->attended_by)->toBe($this->volunteer->id);

    $firstTime = $this->walker->attended_at;
    $this->post(route('admin.registrations.attend', $this->walker))->assertSessionHas('error');
    expect($this->walker->fresh()->attended_at->equalTo($firstTime))->toBeTrue();
});

it('keeps admin-only pages away from sub-users', function () {
    $this->actingAs($this->volunteer);

    $this->get(route('admin.subusers.index'))->assertForbidden();
    $this->get(route('admin.supporttickets'))->assertForbidden();
    $this->post(route('admin.registrations.unattend', $this->walker))->assertForbidden();
});

it('lets an admin undo attendance', function () {
    $this->walker->markAttended($this->volunteer);

    $this->actingAs($this->admin)->post(route('admin.registrations.unattend', $this->walker));
    expect($this->walker->fresh()->attended_at)->toBeNull();
});

it('lets the super admin create, edit and delete sub-users', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.subusers.store'), [
        'first_name' => 'Ravi', 'email' => 'ravi@test.com', 'phone' => '9000000001',
        'password' => 'password1', 'password_confirmation' => 'password1', 'active' => true,
    ])->assertRedirect(route('admin.subusers.index'));

    $ravi = User::where('email', 'ravi@test.com')->firstOrFail();
    expect($ravi->hasRole(Roles::SUB_USER))->toBeTrue();

    // blank password keeps the old one
    $this->put(route('admin.subusers.update', $ravi), [
        'first_name' => 'Ravi K', 'email' => 'ravi@test.com', 'password' => '', 'password_confirmation' => '', 'active' => false,
    ])->assertRedirect();
    $ravi->refresh();
    expect($ravi->first_name)->toBe('Ravi K')
        ->and((int) $ravi->active)->toBe(0)
        ->and(Hash::check('password1', $ravi->password))->toBeTrue();

    $this->delete(route('admin.subusers.destroy', $ravi))->assertRedirect();
    expect(User::find($ravi->id))->toBeNull();
});

it('never lets the sub-user screens touch an admin account', function () {
    $this->actingAs($this->admin)->get(route('admin.subusers.edit', $this->admin))->assertNotFound();
    $this->delete(route('admin.subusers.destroy', $this->admin))->assertNotFound();
});

it('signs out a sub-user who was deactivated', function () {
    $this->volunteer->update(['active' => 0]);

    $this->actingAs($this->volunteer)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

/** Rows of a downloaded .xlsx as arrays of cell values. */
function xlsxRows(string $content): array
{
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $content);
    $reader = new OpenSpout\Reader\XLSX\Reader;
    $reader->open($path);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
    }
    $reader->close();
    unlink($path);

    return $rows;
}

it('lets an admin download registrations as an Excel file', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.registrations.export'));

    $response->assertOk()->assertDownload();
    $rows = xlsxRows($response->streamedContent());

    expect($rows)->toHaveCount(2)
        ->and($rows[0][0])->toBe('Bib')
        ->and($rows[1][0])->toBe($this->walker->bib_number)
        ->and($rows[1][8])->toBe('9876543210')   // WhatsApp kept as text
        ->and($rows[1][11])->toBe('Asthma')
        ->and($rows[1][12])->toBe('No')         // Employee
        ->and($rows[1][14])->toBe('Website');
});

it('applies the list filter to the Excel export', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.registrations.export', ['status' => 'attended']));

    expect(xlsxRows($response->streamedContent()))->toHaveCount(1); // header only
});

it('keeps the Excel export and Add registration away from sub-users', function () {
    $this->actingAs($this->volunteer);

    $this->get(route('admin.registrations.export'))->assertForbidden();
    $this->get(route('admin.registrations.create'))->assertForbidden();
    $this->post(route('admin.registrations.store'), [])->assertForbidden();
});

it('lets an admin add a registration and optionally email the bib', function () {
    Illuminate\Support\Facades\Bus::fake();

    $data = [
        'firstName' => 'Ravi', 'lastName' => 'Das', 'gender' => 'Male',
        'email' => 'ravi@example.com', 'whatsapp' => '9000000002', 'dob' => '1985-01-15',
        'bloodGroup' => 'B+', 'address' => 'New Town, Kolkata', 'emergency' => '9000000003',
        'medical' => 'None', 'privacy' => true, 'employee' => true, 'sendEmail' => true,
    ];

    $this->actingAs($this->admin)->post(route('admin.registrations.store'), $data)
        ->assertSessionHas('success');

    $ravi = WalkathonRegistration::where('email', 'ravi@example.com')->firstOrFail();
    expect($ravi->created_by)->toBe($this->admin->id)
        ->and($ravi->bib_number)->toStartWith('4')
        ->and($ravi->is_employee)->toBeTrue();
    Illuminate\Support\Facades\Bus::assertDispatchedAfterResponse(App\Jobs\SendBibConfirmationMail::class);
});

it('does not email when the admin unticks it', function () {
    Illuminate\Support\Facades\Bus::fake();

    $this->actingAs($this->admin)->post(route('admin.registrations.store'), [
        'firstName' => 'Mita', 'lastName' => 'Roy', 'gender' => 'Female',
        'email' => 'mita@example.com', 'whatsapp' => '9000000004', 'dob' => '1999-03-03',
        'bloodGroup' => 'A+', 'address' => 'Dum Dum, Kolkata', 'emergency' => '9000000005',
        'medical' => 'None', 'privacy' => true, 'sendEmail' => false,
    ])->assertSessionHasNoErrors();

    Illuminate\Support\Facades\Bus::assertNotDispatchedAfterResponse(App\Jobs\SendBibConfirmationMail::class);
});

it('applies the same rules to admin-added registrations', function () {
    $this->actingAs($this->admin)->post(route('admin.registrations.store'), [
        'firstName' => 'Dup', 'lastName' => 'User', 'gender' => 'Male',
        'email' => 'asha@example.com', 'whatsapp' => '9876543210', 'dob' => now()->subYears(2)->toDateString(),
        'bloodGroup' => 'O+', 'address' => 'Somewhere, Kolkata', 'emergency' => '9876543210',
        'medical' => 'None', 'privacy' => false,
    ])->assertSessionHasErrors(['email', 'whatsapp', 'dob', 'emergency', 'privacy']);
});
