<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if (auth()->user()->is_staff || auth()->user()->is_admin) {
            AuditLog::log('auth.login', auth()->user(), null, ['email' => auth()->user()->email]);
        }

        $user = auth()->user();
        $flash = ['success' => 'Welcome back, ' . $user->name . '!'];

        if ($user->is_admin || $user->is_staff) {
            $destination = $user->hasRole(['super-admin', 'ceo'])
                ? route('manage.dashboard', absolute: false)
                : route('manage.home', absolute: false);

            return redirect()->intended($destination)->with($flash);
        }

        return redirect()->intended(route('home', absolute: false))->with($flash);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (auth()->user()->is_staff || auth()->user()->is_admin) {
            AuditLog::log('auth.logout', auth()->user(), null, null);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been successfully logged out.');
    }
}
