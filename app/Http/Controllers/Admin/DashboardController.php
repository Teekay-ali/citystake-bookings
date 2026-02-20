<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Statistics
        $stats = [
            'total_bookings' => Booking::count(),
            'active_bookings' => Booking::where('status', 'confirmed')
                ->where('check_in', '<=', $today)
                ->where('check_out', '>=', $today)
                ->count(),
            'upcoming_bookings' => Booking::where('status', 'confirmed')
                ->where('check_in', '>', $today)
                ->count(),
            'total_revenue' => Booking::where('payment_status', 'paid')->sum('total_amount'),
            'monthly_revenue' => Booking::where('payment_status', 'paid')
                ->whereMonth('created_at', $thisMonth->month)
                ->whereYear('created_at', $thisMonth->year)
                ->sum('total_amount'),
            'total_properties' => Building::count(),
            'total_unit_types' => UnitType::count(),
            'pending_payments' => Booking::where('payment_status', 'pending')->count(),
        ];

        // Recent bookings
        $recentBookings = Booking::with(['building', 'unitType', 'unit', 'user'])
            ->latest()
            ->take(10)
            ->get();

        // Upcoming check-ins (next 7 days)
        $upcomingCheckIns = Booking::with(['building', 'unitType', 'unit'])
            ->where('status', 'confirmed')
            ->whereBetween('check_in', [$today, $today->copy()->addDays(7)])
            ->orderBy('check_in')
            ->get();

        // Revenue chart data (last 12 months)
        $revenueData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Booking::where('payment_status', 'paid')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount');

            $revenueData[] = [
                'month' => $month->format('M Y'),
                'revenue' => (float) $revenue,
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'upcomingCheckIns' => $upcomingCheckIns,
            'revenueData' => $revenueData,
        ]);
    }
}
