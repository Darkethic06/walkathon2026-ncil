<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\WalkathonRegistration;
use App\Support\Roles;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $total = WalkathonRegistration::count();
        $attended = WalkathonRegistration::whereNotNull('attended_at')->count();

        $stats = [
            'registrations' => $total,
            'attended' => $attended,
            'pending' => $total - $attended,
        ];

        if ($request->user()->hasAnyRole(Roles::ADMINS)) {
            $stats['emailsNotSent'] = WalkathonRegistration::whereNull('mail_sent_at')->count();
            $stats['openTickets'] = SupportTicket::where('status', 'open')->count();
        }

        return Inertia::render('Admin/Dashboard', ['stats' => $stats]);
    }
}
