<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!in_array($credentials['email'], $this->allowedEmails(), true)) {
            return back()
                ->withErrors([
                    'email' => 'This account is not allowed to access this dashboard.',
                ])
                ->onlyInput('email');
        }

        if (!Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            return back()
                ->withErrors([
                    'email' => 'The provided credentials are incorrect.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @return array<int, string>
     */
    private function allowedEmails(): array
    {
        return [
            config('auth.joytel_users.dev_email'),
            config('auth.joytel_users.admin_email'),
        ];
    }
}
