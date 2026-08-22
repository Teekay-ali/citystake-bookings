<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UsageAnalyticsController extends Controller
{
    private const RANGES = [7, 30, 90];

    private const SECTION_LABELS = [
        'dashboard' => 'Dashboard', 'home' => 'Home', 'bookings' => 'Bookings',
        'booking-groups' => 'Booking Groups', 'availability' => 'Availability',
        'inspections' => 'Inspections', 'maintenance' => 'Maintenance', 'procurement' => 'Procurement',
        'complaints' => 'Complaints', 'tasks' => 'Tasks', 'staff' => 'Staff',
        'staff-messages' => 'Staff Messages', 'staff-queries' => 'Staff Queries', 'roles' => 'Roles',
        'properties' => 'Properties', 'financial' => 'Financials', 'analytics' => 'Analytics',
        'usage-analytics' => 'Usage Analytics', 'notifications' => 'Notifications', 'messages' => 'Messages',
        'changelogs' => 'Platform Updates', 'guests' => 'Guests', 'vendors' => 'Vendors',
        'stock' => 'Stock', 'audit-logs' => 'Audit Logs', 'enquiries' => 'Enquiries',
        'organizations' => 'Organizations', 'blocked-dates' => 'Blocked Dates',
        'payment-approvals' => 'Payment Approvals', 'emergency-fund' => 'Emergency Fund',
    ];

    private const ACTION_LABELS = [
        'create' => 'New', 'edit' => 'Edit', 'show' => 'View', 'round' => 'Round', 'section' => 'Section',
    ];

    private const ROLE_LABELS = [
        'super-admin' => 'Super Admin', 'ceo' => 'CEO', 'manager' => 'Manager', 'accountant' => 'Accountant',
        'head-of-procurement' => 'Procurement Officer', 'receptionist' => 'Receptionist',
        'quality-control' => 'Quality Control', 'staff' => 'Staff',
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-usage-analytics'), 403);

        $days  = in_array((int) $request->input('range'), self::RANGES, true) ? (int) $request->input('range') : 30;
        $start = Carbon::today()->subDays($days - 1)->startOfDay();

        // Optional drill-down to a single staff/admin user.
        $userId = $request->input('user_id');
        $staff  = User::query()->where(fn ($q) => $q->where('is_staff', true)->orWhere('is_admin', true));
        if ($userId && ! (clone $staff)->whereKey($userId)->exists()) {
            $userId = null;
        }

        // Every aggregation runs off this scoped base query.
        $base = fn () => PageVisit::where('visited_at', '>=', $start)
            ->when($userId, fn ($q) => $q->where('user_id', $userId));

        $total       = $base()->count();
        $activeUsers = $base()->distinct('user_id')->count('user_id');

        return Inertia::render('Admin/UsageAnalytics/Index', [
            'range'    => $days,
            'ranges'   => self::RANGES,
            'userId'   => $userId ? (int) $userId : null,
            'users'    => (clone $staff)->orderBy('name')->get(['id', 'name']),
            'stats'    => [
                'total_visits'  => $total,
                'active_users'  => $activeUsers,
                'avg_per_day'   => $days > 0 ? (int) round($total / $days) : 0,
                'busiest_day'   => $this->busiestDay($base()),
            ],
            'perDay'      => $this->perDay($start, $days, $userId),
            'topPages'    => $this->topPages($base()),
            'activeUsers' => $this->mostActiveUsers($base()),
            'byHour'      => $this->byHour($base()),
            'recent'      => $this->recent($userId),
        ]);
    }

    /** Daily visit counts across the window, zero-filled. */
    private function perDay(Carbon $start, int $days, $userId = null): array
    {
        $rows = PageVisit::where('visited_at', '>=', $start)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->selectRaw('DATE(visited_at) as d, COUNT(*) as c')
            ->groupBy('d')->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key  = $date->toDateString();
            $out[] = ['date' => $date->format('d M'), 'count' => (int) ($rows[$key] ?? 0)];
        }

        return $out;
    }

    private function topPages($query): array
    {
        return $query->whereNotNull('route_name')
            ->selectRaw('route_name, COUNT(*) as c')
            ->groupBy('route_name')->orderByDesc('c')->limit(10)->get()
            ->map(fn ($r) => ['label' => $this->pageLabel($r->route_name), 'count' => (int) $r->c])
            ->all();
    }

    private function mostActiveUsers($query): array
    {
        $rows = $query->selectRaw('user_id, COUNT(*) as c')
            ->groupBy('user_id')->orderByDesc('c')->limit(8)->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->with('roles:id,name')->get()->keyBy('id');

        return $rows->map(function ($r) use ($users) {
            $u = $users->get($r->user_id);
            $role = $u?->roles->first()?->name;

            return [
                'name'  => $u?->name ?? 'Unknown',
                'role'  => $role ? (self::ROLE_LABELS[$role] ?? ucfirst($role)) : null,
                'count' => (int) $r->c,
            ];
        })->all();
    }

    /** Visits per hour of day (0–23), zero-filled. */
    private function byHour($query): array
    {
        $rows = $query->selectRaw('HOUR(visited_at) as h, COUNT(*) as c')
            ->groupBy('h')->pluck('c', 'h');

        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[] = ['hour' => $h, 'count' => (int) ($rows[$h] ?? 0)];
        }

        return $out;
    }

    private function recent($userId = null): array
    {
        return PageVisit::with('user:id,name')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('visited_at')->limit(20)->get()
            ->map(fn ($v) => [
                'user'       => $v->user?->name ?? 'Unknown',
                'page'       => $this->pageLabel($v->route_name),
                'path'       => '/'.$v->path,
                'visited_at' => $v->visited_at->toISOString(),
            ])->all();
    }

    private function busiestDay($query): ?string
    {
        $row = $query->selectRaw('DATE(visited_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderByDesc('c')->first();

        return $row ? Carbon::parse($row->d)->format('D, d M') : null;
    }

    /** Friendly label from a route name, e.g. manage.bookings.show → "Bookings · View". */
    private function pageLabel(?string $routeName): string
    {
        if (! $routeName) {
            return 'Other';
        }

        $parts   = explode('.', str_replace('manage.', '', $routeName));
        $section = self::SECTION_LABELS[$parts[0]] ?? ucfirst(str_replace('-', ' ', $parts[0]));
        $action  = end($parts);

        if (count($parts) <= 1 || $action === 'index' || $action === $parts[0]) {
            return $section;
        }

        return $section.' · '.(self::ACTION_LABELS[$action] ?? ucfirst($action));
    }
}
