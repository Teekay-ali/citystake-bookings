<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Basic Statistics
        $stats = [
            'total_bookings' => Booking::count(),
            'active_bookings' => Booking::where('status', 'confirmed')
                ->where('check_in', '<=', $today)
                ->where('check_out', '>=', $today)
                ->count(),
            'total_properties' => Building::count(),
            'total_users' => User::count(),
        ];

        // Revenue Stats
        $revenue = [
            'total' => Booking::where('payment_status', 'paid')->sum('total_amount'),
            'this_month' => Booking::where('payment_status', 'paid')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'this_year' => Booking::where('payment_status', 'paid')
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
            'last_month' => Booking::where('payment_status', 'paid')
                ->whereYear('created_at', now()->subMonth()->year)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->sum('total_amount'),
        ];

        // Calculate growth percentage
        $revenue['growth_percentage'] = $revenue['last_month'] > 0
            ? round((($revenue['this_month'] - $revenue['last_month']) / $revenue['last_month']) * 100, 1)
            : ($revenue['this_month'] > 0 ? 100 : 0);

        // Monthly Revenue (Last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $total = Booking::where('payment_status', 'paid')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount');

            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'total' => (float) $total,
            ];
        }

        // Revenue by Property
        $revenueByProperty = Booking::where('payment_status', 'paid')
            ->with('building')
            ->select('building_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('building_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'property' => $item->building->name ?? 'Unknown',
                    'total' => $item->total,
                ];
            });

        // Payment Status Breakdown
        $paymentBreakdown = [
            'paid' => Booking::where('payment_status', 'paid')->count(),
            'pending' => Booking::where('payment_status', 'pending')->count(),
        ];

        // Booking Status Breakdown
        $statusBreakdown = [
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
        ];

        // Recent Bookings
        $recentBookings = Booking::with(['building', 'unitType', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Upcoming Check-ins (Next 7 days)
        $upcomingCheckIns = Booking::with(['building', 'unitType'])
            ->where('status', 'confirmed')
            ->whereBetween('check_in', [$today, $today->copy()->addDays(7)])
            ->orderBy('check_in')
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenue' => $revenue,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueByProperty' => $revenueByProperty,
            'paymentBreakdown' => $paymentBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'recentBookings' => $recentBookings,
            'upcomingCheckIns' => $upcomingCheckIns,
        ]);
    }
}
