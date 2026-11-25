<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'phone.regex' => 'Enter a 10 digit phone number.',
        ]);

        SupportTicket::create($data);

        return back()->with('success', 'Thank you! Your support ticket has been submitted. We will contact you soon.');
    }
}
