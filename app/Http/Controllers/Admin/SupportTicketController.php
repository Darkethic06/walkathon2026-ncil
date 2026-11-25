<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/SupportTickets/List', [
            'tickets' => SupportTicket::latest()->paginate(25),
        ]);
    }
}
