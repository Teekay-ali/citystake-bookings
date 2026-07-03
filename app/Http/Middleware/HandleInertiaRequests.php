<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\EmergencyFundRequest;
use App\Models\MaintenanceReport;
use App\Models\PaymentApproval;
use App\Models\ProcurementRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user          = $request->user();
        $isManageRoute = $request->routeIs('manage.*');

        // Compute once — reused across all badge closures
        $buildingIds   = ($user && $isManageRoute && ! $user->hasGlobalAccess())
            ? $user->accessibleBuildingIds()
            : null;

        $applyBuildings = fn ($query) => $buildingIds
            ? $query->whereIn('building_id', $buildingIds)
            : $query;

        return array_merge(parent::share($request), [

            'auth' => [
                'user' => $user ? [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'is_admin'          => $user->is_admin,
                    'is_staff'          => $user->is_staff ?? false,
                    'is_audit_owner'    => (bool) config('audit.owner_email') && $user->email === config('audit.owner_email'),
                    'roles'             => $isManageRoute ? $user->getRoleNames() : [],
                    'permissions'       => $isManageRoute ? $user->getAllPermissions()->pluck('name') : [],
                    'buildings'         => $buildingIds,
                    'email_verified_at' => $user->email_verified_at,
                    'phone'             => $user->phone,
                    'email_marketing'   => (bool) $user->email_marketing,
                    'email_reminders'   => (bool) $user->email_reminders,
                    'email_newsletters' => (bool) $user->email_newsletters,
                ] : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->pull('success'),
                'error'   => fn () => $request->session()->pull('error'),
                'info'    => fn () => $request->session()->pull('info'),
                'warning' => fn () => $request->session()->pull('warning'),
            ],

            'appName' => config('app.name'),

            // ── Badge counts (all lazy closures — skipped on partial Inertia navigations) ──

            'lateCheckoutPendingCount' => fn () => ($user && $isManageRoute)
                ? $applyBuildings(Booking::where('late_checkout_status', 'pending'))->count()
                : 0,

            // Confirmed, paid arrivals due today (or overdue) that haven't been checked in yet.
            'dueCheckIns' => fn () => ($user && $isManageRoute && $user->can('view-bookings'))
                ? $applyBuildings(
                    Booking::where('status', 'confirmed')
                           ->where('payment_status', 'paid')
                           ->whereNull('checked_in_at')
                           ->whereDate('check_in', '<=', now()->toDateString())
                )->count()
                : 0,

            'unreadNotifications' => fn () => ($user && $isManageRoute)
                ? $user->unreadNotifications()->count()
                : 0,

            'unreadMessages' => fn () => ($user && $isManageRoute)
                ? BookingMessage::where('sender_type', 'guest')
                    ->whereNull('read_at')
                    ->whereHas('booking', fn ($q) => $applyBuildings($q))
                    ->count()
                : 0,

            'newEnquiries' => fn () => ($user && $isManageRoute)
                ? $applyBuildings(\App\Models\BookingEnquiry::where('status', 'new'))->count()
                : 0,

            'pendingEmergencyFund' => fn () => ($user && $isManageRoute && ! $user->hasRole('accountant'))
                ? $applyBuildings(
                    EmergencyFundRequest::whereIn('status',
                        $user->hasRole('manager') ? ['pending'] : ['manager_approved']
                    )
                )->count()
                : 0,

            'pendingPaymentApprovals' => fn () => ($user && $isManageRoute && ! $user->hasRole('accountant'))
                ? $applyBuildings(PaymentApproval::where('status', 'pending'))->count()
                : 0,

            'pendingMaintenance' => fn () => ($user && $isManageRoute)
                ? $applyBuildings(
                    MaintenanceReport::whereIn('status', ['pending', 'manager_approved', 'accountant_approved'])
                )->count()
                : 0,

            'pendingProcurement' => fn () => ($user && $isManageRoute)
                ? $applyBuildings(
                    ProcurementRequest::whereIn('status', ['pending', 'accountant_approved'])
                )->count()
                : 0,

            'pendingTasks' => fn () => ($user && $isManageRoute)
                ? $applyBuildings(
                    Task::where('assigned_to', $user->id)
                        ->whereIn('status', ['pending', 'in_progress'])
                )->count()
                : 0,

            'pendingCautionRefunds' => fn () => ($user && $isManageRoute && $user->can('manage-bookings'))
                ? $applyBuildings(
                    Booking::where('caution_refund_requested', true)
                           ->where('caution_fee_refunded', false)
                )->count()
                : 0,

            'unreadChangelogs' => fn () => ($user && $isManageRoute && $user->is_admin)
                ? \App\Models\Changelog::published()
                    ->whereNotIn('id', $user->changelogReads()->pluck('changelog_id'))
                    ->latest('published_at')
                    ->get(['id', 'title', 'body', 'version', 'type', 'published_at'])
                    ->map(fn($c) => [
                        'id'           => $c->id,
                        'title'        => $c->title,
                        'body'         => $c->body,
                        'version'      => $c->version,
                        'type'         => $c->type,
                        'published_at' => $c->published_at->toISOString(),
                    ])
                : [],

            'unreadStaffMessages' => fn () => ($user && $isManageRoute && ($user->is_staff || $user->is_admin))
                ? $user->receivedMessages()
                    ->whereNull('staff_message_recipients.read_at')
                    ->whereNull('staff_message_recipients.deleted_at')
                    ->whereNull('staff_messages.parent_id')
                    ->count()
                : 0,

        ]);
    }
}
