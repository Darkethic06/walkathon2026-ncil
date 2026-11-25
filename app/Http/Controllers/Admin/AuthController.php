<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    // Everyone allowed into the panel, sub-users included.
    const ADMIN_ROLES = Roles::PANEL;

    public function login()
    {
        $user = auth()->user();

        if ($user && $user->active == 1 && $user->hasAnyRole(self::ADMIN_ROLES)) {
            return redirect()->intended('/admin/dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function loginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = auth()->user();

            if ($user->active == 1 && $user->hasAnyRole(self::ADMIN_ROLES)) {
                $request->session()->regenerate();
                return redirect()->intended('/admin/dashboard');
            }

            // Authenticated but not an active admin — don't leave a session behind.
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request){
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
