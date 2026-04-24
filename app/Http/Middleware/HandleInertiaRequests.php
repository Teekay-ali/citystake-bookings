<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\BookingMessage;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'                => $request->user()->id,
                    'name'              => $request->user()->name,
                    'email'             => $request->user()->email,
                    'is_admin'          => $request->user()->is_admin,
                    'is_staff'          => $request->user()?->is_staff ?? false,
                    'roles'             => $request->user()->getRoleNames(),
                    'permissions'       => $request->user()->getAllPermissions()->pluck('name'),
                    'buildings'         => $request->user()->hasGlobalAccess()
                        ? null
                        : $request->user()->buildings()->pluck('buildings.id'),
                    'email_verified_at' => $request->user()->email_verified_at,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'appName' => config('app.name'),
            'lateCheckoutPendingCount' => auth()->check()
                ? Booking::where('late_checkout_status', 'pending')->count()
                : 0,
            'unreadNotifications' => auth()->check() && request()->routeIs('manage.*')
                ? auth()->user()->unreadNotifications()->count()
                : 0,
            'unreadMessages' => auth()->check() && request()->routeIs('manage.*')
                ? BookingMessage::where('sender_type', 'guest')
                    ->whereNull('read_at')
                    ->whereHas('booking', function ($q) {
                        $user = auth()->user();
                        if (!$user->hasGlobalAccess()) {
                            $q->whereIn('building_id', $user->accessibleBuildingIds() ?? []);
                        }
                    })
                    ->count()
                : 0,
        ]);
    }

}
