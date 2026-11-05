<?php

namespace App\Http\Controllers;

use App\Support\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Emails are stored lowercased by app:ensure-admin; a phone keyboard
        // capitalising the first letter shouldn't lock you out.
        $credentials['email'] = strtolower(trim($credentials['email']));

        // Make sure the ADMIN_* login exists with the current password, in
        // case the deploy's start command didn't run app:ensure-admin.
        if (! Admin::configured()) {
            return back()->withErrors(['email' => 'Sign-in isn\'t set up yet: ADMIN_EMAIL and ADMIN_PASSWORD aren\'t set on this Railway service.'])->onlyInput('email');
        }

        Admin::sync();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Log::warning('Sign-in failed', Admin::explainFailure($credentials['email'], $credentials['password']));

            return back()->withErrors(['email' => 'Wrong email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('overview'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
