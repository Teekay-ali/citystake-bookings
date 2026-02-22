<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $buildingId = $request->input('building_id');

        // Get all buildings
        $buildings = Building::where('is_active', true)->get();

        // Calculate overall occupancy
        $overallOccupancy = $this->calculateOverallOccupancy($year, $month, $buildingId);

        // Calculate occupancy by property
        $occupancyByProperty = $this->calculateOccupancyByProperty($year, $month);

        // Calculate monthly occupancy trend (last 6 months)
        $monthlyTrend = $this->calculateMonthlyTrend($buildingId);

        // Get top performing properties
        $topPerformers = $this->getTopPerformers($year, $month);

        // Revenue per available night
        $revenuePerNight = $this->calculateRevenuePerNight($year, $month, $buildingId);

        return Inertia::render('Admin/Analytics/Occupancy', [
            'overallOccupancy' => $overallOccupancy,
            'occupancyByProperty' => $occupancyByProperty,
            'monthlyTrend' => $monthlyTrend,
            'topPerformers' => $topPerformers,
            'revenuePerNight' => $revenuePerNight,
            'buildings' => $buildings,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'building_id' => $buildingId,
            ],
        ]);
    }

    private function calculateOverallOccupancy($year, $month, $buildingId = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $daysInMonth = $endDate->day;

        // Get total units
        $totalUnits = Unit::when($buildingId, function ($query, $buildingId) {
            $query->whereHas('unitType', function ($q) use ($buildingId) {
                $q->where('building_id', $buildingId);
            });
        })
            ->where('is_available', true)
            ->count();

        // Total available nights = units × days in month
        $totalAvailableNights = $totalUnits * $daysInMonth;

        // Calculate booked nights
        $bookedNights = Booking::where('status', '!=', 'cancelled')
            ->when($buildingId, function ($query, $buildingId) {
                $query->where('building_id', $buildingId);
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('check_in', '<=', $startDate)
                            ->where('check_out', '>=', $endDate);
                    });
            })
            ->get()
            ->sum(function ($booking) use ($startDate, $endDate) {
                $checkIn = max($booking->check_in, $startDate);
                $checkOut = min($booking->check_out, $endDate);
                return $checkIn->diffInDays($checkOut);
            });

        $occupancyRate = $totalAvailableNights > 0
            ? round(($bookedNights / $totalAvailableNights) * 100, 1)
            : 0;

        return [
            'rate' => $occupancyRate,
            'booked_nights' => $bookedNights,
            'available_nights' => $totalAvailableNights,
            'total_units' => $totalUnits,
        ];
    }

    private function calculateOccupancyByProperty($year, $month)
    {
        $buildings = Building::where('is_active', true)->get();
        $data = [];

        foreach ($buildings as $building) {
            $occupancy = $this->calculateOverallOccupancy($year, $month, $building->id);
            $data[] = [
                'property' => $building->name,
                'occupancy_rate' => $occupancy['rate'],
                'booked_nights' => $occupancy['booked_nights'],
                'available_nights' => $occupancy['available_nights'],
            ];
        }

        return collect($data)->sortByDesc('occupancy_rate')->values();
    }

    private function calculateMonthlyTrend($buildingId = null)
    {
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $occupancy = $this->calculateOverallOccupancy($date->year, $date->month, $buildingId);

            $data[] = [
                'month' => $date->format('M Y'),
                'rate' => $occupancy['rate'],
            ];
        }

        return $data;
    }

    private function getTopPerformers($year, $month)
    {
        return $this->calculateOccupancyByProperty($year, $month)
            ->take(5);
    }

    private function calculateRevenuePerNight($year, $month, $buildingId = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $totalRevenue = Booking::where('payment_status', 'paid')
            ->when($buildingId, function ($query, $buildingId) {
                $query->where('building_id', $buildingId);
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('check_in', [$startDate, $endDate])
                    ->orWhereBetween('check_out', [$startDate, $endDate]);
            })
            ->sum('total_amount');

        $occupancy = $this->calculateOverallOccupancy($year, $month, $buildingId);
        $availableNights = $occupancy['available_nights'];

        $revPAN = $availableNights > 0
            ? round($totalRevenue / $availableNights, 2)
            : 0;

        return [
            'revenue_per_available_night' => $revPAN,
            'total_revenue' => $totalRevenue,
        ];
    }

}
