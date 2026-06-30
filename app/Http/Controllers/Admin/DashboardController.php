<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\FinancialTransaction;
use App\Models\Unit;
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

        $scopedIds = $this->scopedBuildingIds();

        // Occupancy — units occupied today vs total units in scope
        $totalUnits = Unit::whereHas('unitType', fn ($q) => $q->whereIn('building_id', $scopedIds))->count();

        $occupiedUnits = $bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->whereNotNull('unit_id')
            ->distinct('unit_id')
            ->count('unit_id');

        $stats = [
            'total_bookings'   => $bookings()->count(),
            'active_bookings'  => $bookings()->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in', '<=', $today)
                ->where('check_out', '>=', $today)
                ->count(),
            'total_properties' => $this->accessibleBuildings()->count(),
            'total_guests'     => User::where('is_staff', false)->where('is_admin', false)->count(),
            'late_checkouts'   => $bookings()->where('late_checkout_status', 'pending')->count(),
            'occupied_units'   => $occupiedUnits,
            'total_units'      => $totalUnits,
            'occupancy_rate'   => $totalUnits > 0 ? (int) round($occupiedUnits / $totalUnits * 100) : 0,
        ];

        // Single query for all revenue aggregates
        $now       = now();
        $lastMonth = $now->copy()->subMonth();
        $revenueRow = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'income')
            ->selectRaw("
                SUM(amount) as total,
                SUM(CASE WHEN YEAR(transaction_date) = ? AND MONTH(transaction_date) = ? THEN amount ELSE 0 END) as this_month,
                SUM(CASE WHEN YEAR(transaction_date) = ?                                THEN amount ELSE 0 END) as this_year,
                SUM(CASE WHEN YEAR(transaction_date) = ? AND MONTH(transaction_date) = ? THEN amount ELSE 0 END) as last_month
            ", [$now->year, $now->month, $now->year, $lastMonth->year, $lastMonth->month])
            ->first();

        $revenue = [
            'total'      => (float) ($revenueRow->total      ?? 0),
            'this_month' => (float) ($revenueRow->this_month ?? 0),
            'this_year'  => (float) ($revenueRow->this_year  ?? 0),
            'last_month' => (float) ($revenueRow->last_month ?? 0),
        ];

        $revenue['growth_percentage'] = $revenue['last_month'] > 0
            ? round((($revenue['this_month'] - $revenue['last_month']) / $revenue['last_month']) * 100, 1)
            : ($revenue['this_month'] > 0 ? 100 : 0);

        // 12-month income trend (unified with the hero KPI — FinancialTransaction income)
        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $monthlyRevenueRaw = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'income')
            ->where('transaction_date', '>=', $start->toDateString())
            ->selectRaw('YEAR(transaction_date) as yr, MONTH(transaction_date) as mo, SUM(amount) as total')
            ->groupBy('yr', 'mo')
            ->get()
            ->keyBy(fn ($row) => sprintf('%d-%02d', $row->yr, $row->mo));

        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key   = $month->format('Y-m');
            $monthlyRevenue[] = [
                'month' => $month->format('M Y'),
                'total' => (float) ($monthlyRevenueRaw[$key]?->total ?? 0),
            ];
        }

        $revenueRows = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'income')
            ->select('building_id', DB::raw('SUM(amount) as total'))
            ->groupBy('building_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $buildingNames = Building::whereIn('id', $revenueRows->pluck('building_id'))->pluck('name', 'id');
        $revenueByProperty = $revenueRows->map(fn ($r) => [
            'property' => $buildingNames[$r->building_id] ?? 'Unknown',
            'total'    => (float) $r->total,
        ])->values();

        // Single query each instead of 2 + 6 separate counts
        $paymentCounts = $bookings()
            ->selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status');

        $paymentBreakdown = [
            'paid'    => (int) ($paymentCounts['paid']    ?? 0),
            'pending' => (int) ($paymentCounts['pending'] ?? 0),
        ];

        $statusCounts = $bookings()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusBreakdown = [
            'confirmed'  => (int) ($statusCounts['confirmed']  ?? 0),
            'pending'    => (int) ($statusCounts['pending']    ?? 0),
            'cancelled'  => (int) ($statusCounts['cancelled']  ?? 0),
            'completed'  => (int) ($statusCounts['completed']  ?? 0),
            'checked_in' => (int) ($statusCounts['checked_in'] ?? 0),
            'paused'     => (int) ($statusCounts['paused']     ?? 0),
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
