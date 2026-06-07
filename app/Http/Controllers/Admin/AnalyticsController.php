<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\FinancialTransaction;
use App\Models\Unit;
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        $user = auth()->user();
        $canViewAnalytics  = $user->can('view-analytics');
        $canViewFinancials = $user->can('view-financials');

        abort_unless($canViewAnalytics || $canViewFinancials, 403);

        $request->validate([
            'tab'         => 'nullable|in:occupancy,revenue,financial',
            'year'        => 'nullable|integer|digits:4|min:2020|max:' . (now()->year + 1),
            'month'       => 'nullable|integer|min:1|max:12',
            'building_id' => 'nullable|integer|exists:buildings,id',
        ]);

        // Default tab based on permissions
        $tab = $request->input('tab', $canViewAnalytics ? 'occupancy' : 'financial');

        // If user requests a tab they can't see, fall back
        if ($tab === 'financial' && !$canViewFinancials) $tab = 'occupancy';
        if (in_array($tab, ['occupancy', 'revenue']) && !$canViewAnalytics) $tab = 'financial';

        $year       = (int) $request->input('year', now()->year);
        $month      = (int) $request->input('month', now()->month);
        $buildingId = $request->input('building_id');

        $buildings         = $this->accessibleBuildings()->get(['id', 'name']);
        $scopedBuildingIds = $user->hasGlobalAccess() ? null : $user->accessibleBuildingIds();

        // Validate building filter is within scope
        if ($buildingId && $scopedBuildingIds && !in_array((int)$buildingId, $scopedBuildingIds)) {
            $buildingId = null;
        }
        $effectiveBuildingId = $buildingId ? (int)$buildingId : null;
        $queryIds = $scopedBuildingIds ?? $buildings->pluck('id')->toArray();
        $scopedIds = $effectiveBuildingId ? [$effectiveBuildingId] : $queryIds;

        $data = [
            'tab'       => $tab,
            'buildings' => $buildings,
            'filters'   => [
                'tab'         => $tab,
                'year'        => $year,
                'month'       => $month,
                'building_id' => $buildingId,
            ],
            'canViewAnalytics'  => $canViewAnalytics,
            'canViewFinancials' => $canViewFinancials,
        ];

        if ($tab === 'occupancy' && $canViewAnalytics) {
            $data = array_merge($data, $this->occupancyData($year, $month, $effectiveBuildingId, $scopedBuildingIds));
        }

        if ($tab === 'revenue' && $canViewAnalytics) {
            $data = array_merge($data, $this->revenueData($year, $scopedIds));
        }

        if ($tab === 'financial' && $canViewFinancials) {
            $data = array_merge($data, $this->financialData($year, $scopedIds));
        }

        return Inertia::render('Admin/Analytics/Index', $data);
    }

    // ─────────────────────────────────────────────────────────────
    // OCCUPANCY TAB
    // ─────────────────────────────────────────────────────────────

    private function occupancyData(int $year, int $month, ?int $buildingId, ?array $scopedBuildingIds): array
    {
        return [
            'occupancy' => [
                'overall'      => $this->calcOccupancy($year, $month, $buildingId, $scopedBuildingIds),
                'by_property'  => $this->occupancyByProperty($year, $month, $scopedBuildingIds),
                'monthly_trend'=> $this->occupancyTrend($buildingId, $scopedBuildingIds),
                'avg_length_of_stay'  => $this->avgLengthOfStay($year, $month, $buildingId, $scopedBuildingIds),
                'cancellation_rate'   => $this->cancellationRate($year, $month, $buildingId, $scopedBuildingIds),
                'avg_lead_time'       => $this->avgLeadTime($year, $month, $buildingId, $scopedBuildingIds),
            ],
        ];
    }

    private function calcOccupancy(int $year, int $month, ?int $buildingId, ?array $scopedBuildingIds): array
    {
        $startDate   = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate     = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $endDate->day;

        $totalUnits = Unit::when($buildingId, fn($q) =>
        $q->whereHas('unitType', fn($q2) => $q2->where('building_id', $buildingId))
        )
            ->when($scopedBuildingIds && !$buildingId, fn($q) =>
            $q->whereHas('unitType', fn($q2) => $q2->whereIn('building_id', $scopedBuildingIds))
            )
            ->where('is_available', true)
            ->count();

        $totalAvailableNights = $totalUnits * $daysInMonth;

        $bookedNights = (int) DB::table('bookings')
            ->whereNotIn('status', ['cancelled', 'paused'])
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds))
            ->where('check_in', '<', $endDate->copy()->addDay()->toDateString())
            ->where('check_out', '>', $startDate->toDateString())
            ->whereNull('deleted_at')
            ->selectRaw('SUM(DATEDIFF(LEAST(check_out, ?), GREATEST(check_in, ?))) as nights', [
                $endDate->copy()->addDay()->toDateString(),
                $startDate->toDateString(),
            ])
            ->value('nights') ?? 0;

        return [
            'rate'             => $totalAvailableNights > 0 ? round(($bookedNights / $totalAvailableNights) * 100, 1) : 0,
            'booked_nights'    => (int) $bookedNights,
            'available_nights' => (int) $totalAvailableNights,
            'total_units'      => (int) $totalUnits,
        ];
    }

    private function occupancyByProperty(int $year, int $month, ?array $scopedBuildingIds): array
    {
        $buildings = Building::where('is_active', true)
            ->when($scopedBuildingIds, fn($q) => $q->whereIn('id', $scopedBuildingIds))
            ->get();

        return $buildings->map(function ($building) use ($year, $month) {
            $occ = $this->calcOccupancy($year, $month, $building->id, null);
            return [
                'property'        => $building->name,
                'occupancy_rate'  => $occ['rate'],
                'booked_nights'   => $occ['booked_nights'],
                'available_nights'=> $occ['available_nights'],
            ];
        })->sortByDesc('occupancy_rate')->values()->toArray();
    }

    private function occupancyTrend(?int $buildingId, ?array $scopedBuildingIds): array
    {
        return collect(range(5, 0))->map(function ($i) use ($buildingId, $scopedBuildingIds) {
            $date = now()->subMonths($i);
            $occ  = $this->calcOccupancy($date->year, $date->month, $buildingId, $scopedBuildingIds);
            return ['month' => $date->format('M Y'), 'rate' => $occ['rate']];
        })->toArray();
    }

    private function avgLengthOfStay(int $year, int $month, ?int $buildingId, ?array $scopedBuildingIds): float
    {
        $avg = Booking::where('payment_status', 'paid')
            ->whereYear('check_in', $year)->whereMonth('check_in', $month)
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds))
            ->avg('nights');

        return round((float)$avg, 1);
    }

    private function cancellationRate(int $year, int $month, ?int $buildingId, ?array $scopedBuildingIds): float
    {
        $base = Booking::whereYear('created_at', $year)->whereMonth('created_at', $month)
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds));

        $total      = (clone $base)->count();
        $cancelled  = (clone $base)->where('status', 'cancelled')->count();

        return $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
    }

    private function avgLeadTime(int $year, int $month, ?int $buildingId, ?array $scopedBuildingIds): float
    {
        // Lead time = days between booking creation and check-in date
        $avg = DB::table('bookings')
            ->where('payment_status', 'paid')
            ->whereYear('check_in', $year)
            ->whereMonth('check_in', $month)
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds))
            ->whereNull('deleted_at')
            ->selectRaw('AVG(GREATEST(0, DATEDIFF(check_in, created_at))) as avg_lead')
            ->value('avg_lead') ?? 0;

        return round((float) $avg, 1);
    }

    // ─────────────────────────────────────────────────────────────
    // REVENUE TAB
    // ─────────────────────────────────────────────────────────────

    private function revenueData(int $year, array $scopedIds): array
    {
        // 12-month revenue trend
        $monthlyTrend = collect(range(11, 0))->map(function ($i) use ($scopedIds) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $revenue = Booking::where('payment_status', 'paid')
                ->whereIn('building_id', $scopedIds)
                ->whereBetween('check_in', [$start, $end])
                ->sum('total_amount');

            $bookingCount = Booking::where('payment_status', 'paid')
                ->whereIn('building_id', $scopedIds)
                ->whereBetween('check_in', [$start, $end])
                ->count();

            $bookedNights = Booking::where('payment_status', 'paid')
                ->whereIn('building_id', $scopedIds)
                ->whereBetween('check_in', [$start, $end])
                ->sum('nights');

            return [
                'month'         => $date->format('M Y'),
                'revenue'       => (float)$revenue,
                'bookings'      => $bookingCount,
                'adr'           => $bookedNights > 0 ? round($revenue / $bookedNights, 2) : 0,
            ];
        })->toArray();

        // Revenue by building
        $revenueByBuilding = Booking::where('payment_status', 'paid')
            ->whereIn('building_id', $scopedIds)
            ->whereYear('check_in', $year)
            ->with('building:id,name')
            ->select('building_id', \DB::raw('SUM(total_amount) as total'), \DB::raw('COUNT(*) as bookings'), \DB::raw('SUM(nights) as nights'))
            ->groupBy('building_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'property' => $r->building->name ?? 'Unknown',
                'revenue'  => (float)$r->total,
                'bookings' => $r->bookings,
                'adr'      => $r->nights > 0 ? round($r->total / $r->nights, 2) : 0,
            ])->toArray();

        // Revenue by unit type
        $revenueByUnitType = Booking::where('payment_status', 'paid')
            ->whereIn('building_id', $scopedIds)
            ->whereYear('check_in', $year)
            ->with('unitType:id,name,bedroom_type')
            ->select('unit_type_id', \DB::raw('SUM(total_amount) as total'), \DB::raw('COUNT(*) as bookings'), \DB::raw('SUM(nights) as nights'))
            ->groupBy('unit_type_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'unit_type' => $r->unitType->name ?? 'Unknown',
                'bedroom'   => $r->unitType->bedroom_type ?? '',
                'revenue'   => (float)$r->total,
                'bookings'  => $r->bookings,
                'adr'       => $r->nights > 0 ? round($r->total / $r->nights, 2) : 0,
            ])->toArray();

        // YoY + KPIs — single query for both years, grouped by year+month
        $yoyRows = Booking::where('payment_status', 'paid')
            ->whereIn('building_id', $scopedIds)
            ->whereIn(DB::raw('YEAR(check_in)'), [$year - 1, $year])
            ->selectRaw('YEAR(check_in) as yr, MONTH(check_in) as mo, SUM(total_amount) as total, COUNT(*) as cnt, SUM(nights) as nights')
            ->groupBy('yr', 'mo')
            ->get()
            ->groupBy('yr');

        $thisYearRows = $yoyRows->get($year,      collect())->keyBy('mo');
        $lastYearRows = $yoyRows->get($year - 1,  collect())->keyBy('mo');

        $yoy = collect(range(1, 12))->map(fn ($m) => [
            'month'     => Carbon::create($year, $m, 1)->format('M'),
            'this_year' => (float) ($thisYearRows->get($m)?->total ?? 0),
            'last_year' => (float) ($lastYearRows->get($m)?->total ?? 0),
        ])->toArray();

        $totalRevenue  = $thisYearRows->sum('total');
        $totalBookings = $thisYearRows->sum('cnt');
        $totalNights   = $thisYearRows->sum('nights');
        $lastYearRevenue = $lastYearRows->sum('total');
        $yoyGrowth = $lastYearRevenue > 0 ? round((($totalRevenue - $lastYearRevenue) / $lastYearRevenue) * 100, 1) : null;

        return [
            'revenue' => [
                'monthly_trend'      => $monthlyTrend,
                'by_building'        => $revenueByBuilding,
                'by_unit_type'       => $revenueByUnitType,
                'yoy'                => $yoy,
                'total_revenue'      => (float)$totalRevenue,
                'total_bookings'     => $totalBookings,
                'adr'                => $totalNights > 0 ? round($totalRevenue / $totalNights, 2) : 0,
                'yoy_growth'         => $yoyGrowth,
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // FINANCIAL SUMMARY TAB
    // ─────────────────────────────────────────────────────────────

    private function financialData(int $year, array $scopedIds): array
    {
        // 12-month income vs expense trend
        $trend = collect(range(11, 0))->map(function ($i) use ($scopedIds) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $income   = FinancialTransaction::whereIn('building_id', $scopedIds)->where('type', 'income')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $expenses = FinancialTransaction::whereIn('building_id', $scopedIds)->where('type', 'expense')->whereBetween('transaction_date', [$start, $end])->sum('amount');

            return [
                'month'    => $date->format('M Y'),
                'income'   => (float)$income,
                'expenses' => (float)$expenses,
                'net'      => (float)($income - $expenses),
            ];
        })->toArray();

        // Expense breakdown by category for the year
        $expenseByCategory = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $year)
            ->select('category', \DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'category' => \App\Models\FinancialTransaction::categoryLabels()[$r->category] ?? $r->category,
                'total'    => (float)$r->total,
            ])->toArray();

        // Year KPIs
        $startYear = Carbon::create($year)->startOfYear()->toDateString();
        $endYear   = Carbon::create($year)->endOfYear()->toDateString();

        $yearIncome   = FinancialTransaction::whereIn('building_id', $scopedIds)->where('type', 'income')->whereBetween('transaction_date', [$startYear, $endYear])->sum('amount');
        $yearExpenses = FinancialTransaction::whereIn('building_id', $scopedIds)->where('type', 'expense')->whereBetween('transaction_date', [$startYear, $endYear])->sum('amount');

        // Profit margin trend (monthly)
        $marginTrend = collect($trend)->map(fn($t) => [
            'month'  => $t['month'],
            'margin' => $t['income'] > 0 ? round((($t['income'] - $t['expenses']) / $t['income']) * 100, 1) : 0,
        ])->toArray();

        return [
            'financial' => [
                'trend'               => $trend,
                'expense_by_category' => $expenseByCategory,
                'margin_trend'        => $marginTrend,
                'year_income'         => (float)$yearIncome,
                'year_expenses'       => (float)$yearExpenses,
                'year_net'            => (float)($yearIncome - $yearExpenses),
                'profit_margin'       => $yearIncome > 0 ? round((($yearIncome - $yearExpenses) / $yearIncome) * 100, 1) : 0,
            ],
        ];
    }
}
