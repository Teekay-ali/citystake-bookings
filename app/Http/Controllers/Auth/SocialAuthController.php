<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected array $allowedProviders = [];

    public function __construct()
    {
        $this->allowedProviders = config('services.social_providers', []);
    }

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, $this->allowedProviders), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, $this->allowedProviders), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception) {
            return redirect()->route('login')
                ->withErrors(['social' => 'Authentication failed. Please try again.']);
        }

        // Find existing social account
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'provider_token'         => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
            ]);

            if ($socialAccount->user->is_staff || $socialAccount->user->is_admin) {
                return redirect()->route('login')
                    ->withErrors(['social' => 'Staff accounts must sign in with email and password.']);
            }

            Auth::login($socialAccount->user, remember: true);

            return redirect()->intended(route('home'));
        }

        // Find or create user by email
        $user = User::firstOrNew(['email' => $socialUser->getEmail()]);

        if (! $user->exists) {
            $user->name              = $socialUser->getName();
            $user->email             = $socialUser->getEmail();
            $user->password          = null;
            $user->email_verified_at = now();
            $user->save();

            event(new Registered($user));
        }

        // Link the social account
        SocialAccount::create([
            'user_id'                => $user->id,
            'provider'               => $provider,
            'provider_id'            => $socialUser->getId(),
            'provider_token'         => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
        ]);

        if ($user->is_staff || $user->is_admin) {
            return redirect()->route('login')
                ->withErrors(['social' => 'Staff accounts must sign in with email and password.']);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('home'));
    }
}
