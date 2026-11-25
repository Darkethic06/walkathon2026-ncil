<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Frontend/Index');
    }

    public function privacyPolicy()
    {
        return Inertia::render('Frontend/PrivacyPolicy');
    }
}
