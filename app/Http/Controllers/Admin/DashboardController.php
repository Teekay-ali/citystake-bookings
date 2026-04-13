<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\User;
use App\Traits\ScopedByBuilding;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ScopedByBuilding;

    public function index()
    {
        $today     = Carbon::today();
        $user      = auth()->user();
        $isGlobal  = $user->hasGlobalAccess();
        $buildingIds = $user->accessibleBuildingIds();

        // Scoped booking query macro
        $bookings = fn() => $isGlobal
            ? Booking::query()
            : Booking::whereIn('building_id', $buildingIds ?? []);

        $stats = [
            'total_bookings'   => $bookings()->count(),
            'active_bookings'  => $bookings()->where('status', 'confirmed')
                ->where('check_in', '<=', $today)
                ->where('check_out', '>=', $today)
                ->count(),
            'total_properties' => $this->accessibleBuildings()->count(),
            'total_users'      => User::count(),
        ];

        $revenue = [
            'total'      => $bookings()->where('payment_status', 'paid')->sum('total_amount'),
            'this_month' => $bookings()->where('payment_status', 'paid')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'this_year'  => $bookings()->where('payment_status', 'paid')
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
            'last_month' => $bookings()->where('payment_status', 'paid')
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('total_amount'),
        ];

        $revenue['growth_percentage'] = $revenue['last_month'] > 0
            ? round((($revenue['this_month'] - $revenue['last_month']) / $revenue['last_month']) * 100, 1)
            : ($revenue['this_month'] > 0 ? 100 : 0);

        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'total' => (float) $bookings()
                    ->where('payment_status', 'paid')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('total_amount'),
            ];
        }

        $revenueByProperty = $bookings()
            ->where('payment_status', 'paid')
            ->with('building')
            ->select('building_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('building_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'property' => $item->building->name ?? 'Unknown',
                'total'    => $item->total,
            ]);

        $paymentBreakdown = [
            'paid'    => $bookings()->where('payment_status', 'paid')->count(),
            'pending' => $bookings()->where('payment_status', 'pending')->count(),
        ];

        $statusBreakdown = [
            'confirmed' => $bookings()->where('status', 'confirmed')->count(),
            'pending'   => $bookings()->where('status', 'pending')->count(),
            'cancelled' => $bookings()->where('status', 'cancelled')->count(),
            'completed' => $bookings()->where('status', 'completed')->count(),
        ];

        $recentBookings = $bookings()
            ->with(['building', 'unitType', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $upcomingCheckIns = $bookings()
            ->with(['building', 'unitType'])
            ->where('status', 'confirmed')
            ->whereBetween('check_in', [$today, $today->copy()->addDays(7)])
            ->orderBy('check_in')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats'              => $stats,
            'revenue'            => $revenue,
            'monthlyRevenue'     => $monthlyRevenue,
            'revenueByProperty'  => $revenueByProperty,
            'paymentBreakdown'   => $paymentBreakdown,
            'statusBreakdown'    => $statusBreakdown,
            'recentBookings'     => $recentBookings,
            'upcomingCheckIns'   => $upcomingCheckIns,
        ]);
    }
}
