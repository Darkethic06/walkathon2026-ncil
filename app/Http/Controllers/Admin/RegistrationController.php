<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBibConfirmationMail;
use App\Jobs\SendBibWhatsApp;
use App\Models\WalkathonRegistration;
use App\Support\RegistrationForm;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $full = $request->user()->hasAnyRole(Roles::ADMINS);

        $registrations = $this->filteredQuery($request, $full)
            ->with('attendedBy:id,first_name,last_name')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (WalkathonRegistration $r) => $r->toPanelArray($full));

        return Inertia::render('Admin/Registrations/List', [
            'registrations' => $registrations,
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function show(Request $request, WalkathonRegistration $registration)
    {
        $registration->load('attendedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name');

        return Inertia::render('Admin/Registrations/Show', [
            'registration' => $registration->toPanelArray($request->user()->hasAnyRole(Roles::ADMINS)),
        ]);
    }

    /** Admins only: add a registration by hand (phone call, walk-in, ...). */
    public function create()
    {
        return Inertia::render('Admin/Registrations/Create', [
            'bloodGroups' => RegistrationForm::BLOOD_GROUPS,
            'minAge' => RegistrationForm::MIN_AGE,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            RegistrationForm::rules() + ['sendEmail' => ['boolean'], 'sendWhatsApp' => ['boolean']],
            RegistrationForm::messages() + ['privacy.accepted' => 'Confirm the walker has agreed to the Privacy Policy.'],
        );

        $registration = WalkathonRegistration::create(
            RegistrationForm::toAttributes($data) + ['created_by' => $request->user()->id]
        );

        if ($request->boolean('sendEmail')) {
            SendBibConfirmationMail::dispatchAfterResponse($registration);
        }
        if ($request->boolean('sendWhatsApp')) {
            SendBibWhatsApp::dispatchAfterResponse($registration);
        }

        return redirect()->route('admin.registrations.show', $registration)
            ->with('success', "Registration added. Bib number: {$registration->bib_number}.");
    }

    /**
     * Admins only: download the registrations as an Excel file. Uses the same
     * search / status filter as the list, so "what you see is what you get".
     */
    public function export(Request $request)
    {
        $query = $this->filteredQuery($request, true)
            ->with('attendedBy:id,first_name,last_name', 'createdBy:id,first_name,last_name')
            ->orderBy('id');

        $filename = 'walkathon-registrations-'.now('Asia/Kolkata')->format('Y-m-d-Hi').'.xlsx';

        return response()->streamDownload(function () use ($query) {
            $options = new Options;
            $options->setColumnWidth(10, 1);          // Bib
            $options->setColumnWidth(16, 2, 3);       // names
            $options->setColumnWidth(30, 8, 11, 12);  // email, address, medical
            $options->setColumnWidthForRange(15, 4, 7);
            $options->setColumnWidthForRange(20, 13, 18);

            $writer = new Writer($options);
            $writer->openToFile('php://output');

            $header = (new Style)->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor('0F6478');
            $writer->addRow(Row::fromValuesWithStyle([
                'Bib', 'First name', 'Last name', 'Gender', 'Age', 'Date of birth', 'Blood group',
                'Email', 'WhatsApp', 'Emergency contact', 'Address', 'Medical history',
                'Registered at', 'Added by', 'Confirmation email', 'Attended', 'Attended at', 'Checked in by',
            ], $header));

            $fmt = fn ($dt) => $dt?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? '';

            // lazy(): streams rows in chunks, so a big export doesn't fill memory.
            foreach ($query->lazy(500) as $r) {
                // Phone numbers and bibs are written as text so Excel keeps them exact.
                $writer->addRow(Row::fromValues([
                    (string) $r->bib_number,
                    $r->first_name,
                    $r->last_name,
                    $r->gender,
                    $r->dob?->age,
                    $r->dob?->format('d M Y') ?? '',
                    $r->blood_group,
                    $r->email,
                    (string) $r->whatsapp,
                    (string) $r->emergency_contact,
                    $r->address,
                    $r->medical_history ?? '',
                    $fmt($r->created_at),
                    $r->createdBy?->full_name ?? 'Website',
                    $r->mail_sent_at ? 'Sent' : 'Not sent',
                    $r->attended_at ? 'Yes' : 'No',
                    $fmt($r->attended_at),
                    $r->attendedBy?->full_name ?? '',
                ]));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function attend(Request $request, WalkathonRegistration $registration)
    {
        if ($registration->attended_at) {
            return back()->with('error', "Bib {$registration->bib_number} was already marked as attended.");
        }

        $registration->markAttended($request->user());

        return back()->with('success', "Bib {$registration->bib_number} ({$registration->full_name}) marked as attended.");
    }

    /** Admins only: undo a mistaken check-in. */
    public function unattend(WalkathonRegistration $registration)
    {
        $registration->clearAttendance();

        return back()->with('success', "Attendance removed for bib {$registration->bib_number}.");
    }

    /** Search (?q=) and attendance filter (?status=attended|pending). */
    protected function filteredQuery(Request $request, bool $full): Builder
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        return WalkathonRegistration::query()
            ->when($search !== '', function ($query) use ($search, $full) {
                $query->where(function ($q) use ($search, $full) {
                    $q->where('bib_number', 'like', "{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    // Volunteers can't see email / WhatsApp, so they can't search by them either.
                    if ($full) {
                        $q->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%");
                    }
                });
            })
            ->when($status === 'attended', fn ($q) => $q->whereNotNull('attended_at'))
            ->when($status === 'pending', fn ($q) => $q->whereNull('attended_at'));
    }
}
