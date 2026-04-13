<?php

namespace App\Http\Controllers\Admin;

use App\Traits\ScopedByBuilding;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class OccupancyAnalyticsController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        $year       = $request->input('year', now()->year);
        $month      = $request->input('month', now()->month);
        $buildingId = $request->input('building_id');

        $user        = auth()->user();
        $buildings   = $this->accessibleBuildings()->get();

        // For scoped users, restrict to their accessible buildings
        // If they've also filtered by a specific building, honour that too
        $scopedBuildingIds = $user->hasGlobalAccess()
            ? null
            : $user->accessibleBuildingIds();

        // If a specific building filter is applied, validate it's within scope
        $effectiveBuildingId = $buildingId;
        if ($buildingId && $scopedBuildingIds && !in_array($buildingId, $scopedBuildingIds)) {
            $effectiveBuildingId = null; // ignore out-of-scope filter
        }

        $overallOccupancy    = $this->calculateOverallOccupancy($year, $month, $effectiveBuildingId, $scopedBuildingIds);
        $occupancyByProperty = $this->calculateOccupancyByProperty($year, $month, $scopedBuildingIds);
        $monthlyTrend        = $this->calculateMonthlyTrend($effectiveBuildingId, $scopedBuildingIds);
        $topPerformers       = $this->getTopPerformers($year, $month, $scopedBuildingIds);
        $revenuePerNight     = $this->calculateRevenuePerNight($year, $month, $effectiveBuildingId, $scopedBuildingIds);

        return Inertia::render('Admin/Analytics/Occupancy', [
            'overallOccupancy'    => $overallOccupancy,
            'occupancyByProperty' => $occupancyByProperty,
            'monthlyTrend'        => $monthlyTrend,
            'topPerformers'       => $topPerformers,
            'revenuePerNight'     => $revenuePerNight,
            'buildings'           => $buildings,
            'filters'             => [
                'year'        => $year,
                'month'       => $month,
                'building_id' => $buildingId,
            ],
        ]);
    }

    private function calculateOverallOccupancy($year, $month, $buildingId = null, $scopedBuildingIds = null)
    {
        $startDate   = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate     = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $endDate->day;

        $totalUnits = Unit::when($buildingId, function ($query) use ($buildingId) {
            $query->whereHas('unitType', fn($q) => $q->where('building_id', $buildingId));
        })
            ->when($scopedBuildingIds && !$buildingId, function ($query) use ($scopedBuildingIds) {
                $query->whereHas('unitType', fn($q) => $q->whereIn('building_id', $scopedBuildingIds));
            })
            ->where('is_available', true)
            ->count();

        $totalAvailableNights = $totalUnits * $daysInMonth;

        $bookedNights = Booking::where('status', '!=', 'cancelled')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds))
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate])
                    ->orWhere(fn($q) => $q->where('check_in', '<=', $startDate)->where('check_out', '>=', $endDate));
            })
            ->get()
            ->sum(function ($booking) use ($startDate, $endDate) {
                $checkIn  = max($booking->check_in, $startDate);
                $checkOut = min($booking->check_out, $endDate);
                return $checkIn->diffInDays($checkOut);
            });

        $occupancyRate = $totalAvailableNights > 0
            ? round(($bookedNights / $totalAvailableNights) * 100, 1)
            : 0;

        return [
            'rate'              => $occupancyRate,
            'booked_nights'     => $bookedNights,
            'available_nights'  => $totalAvailableNights,
            'total_units'       => $totalUnits,
        ];
    }

    private function calculateOccupancyByProperty($year, $month, $scopedBuildingIds = null)
    {
        $buildings = Building::where('is_active', true)
            ->when($scopedBuildingIds, fn($q) => $q->whereIn('id', $scopedBuildingIds))
            ->get();

        return collect($buildings)->map(function ($building) use ($year, $month) {
            $occupancy = $this->calculateOverallOccupancy($year, $month, $building->id);
            return [
                'property'        => $building->name,
                'occupancy_rate'  => $occupancy['rate'],
                'booked_nights'   => $occupancy['booked_nights'],
                'available_nights'=> $occupancy['available_nights'],
            ];
        })->sortByDesc('occupancy_rate')->values();
    }

    private function calculateMonthlyTrend($buildingId = null, $scopedBuildingIds = null)
    {
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $occupancy = $this->calculateOverallOccupancy($date->year, $date->month, $buildingId, $scopedBuildingIds);
            $data[] = [
                'month' => $date->format('M Y'),
                'rate'  => $occupancy['rate'],
            ];
        }

        return $data;
    }

    private function getTopPerformers($year, $month, $scopedBuildingIds = null)
    {
        return $this->calculateOccupancyByProperty($year, $month, $scopedBuildingIds)->take(5);
    }

    private function calculateRevenuePerNight($year, $month, $buildingId = null, $scopedBuildingIds = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth();

        $totalRevenue = Booking::where('payment_status', 'paid')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->when($scopedBuildingIds && !$buildingId, fn($q) => $q->whereIn('building_id', $scopedBuildingIds))
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate]);
            })
            ->sum('total_amount');

        $occupancy       = $this->calculateOverallOccupancy($year, $month, $buildingId, $scopedBuildingIds);
        $availableNights = $occupancy['available_nights'];

        return [
            'revenue_per_available_night' => $availableNights > 0 ? round($totalRevenue / $availableNights, 2) : 0,
            'total_revenue'               => $totalRevenue,
        ];
    }

}
