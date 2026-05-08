<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AvailabilityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('manage-availability'), 403);

        $user      = auth()->user();
        $startDate = $request->start ? Carbon::parse($request->start) : Carbon::today();
        $days      = 30;
        $endDate   = $startDate->copy()->addDays($days - 1);

        $buildingsQuery = Building::with([
            'unitTypes' => fn($q) => $q->where('is_active', true)->orderBy('name')->with([
                'units' => fn($q) => $q->whereIn('status', ['available', 'maintenance'])->orderBy('unit_number'),
            ]),
        ])->where('is_active', true);

        if (! $user->hasGlobalAccess()) {
            $buildingsQuery->whereIn('id', $user->accessibleBuildingIds());
        }

        if ($request->filled('building_id')) {
            $buildingsQuery->where('id', $request->building_id);
        }

        $buildings = $buildingsQuery->get();

        $unitIds = $buildings->flatMap(
            fn($b) => $b->unitTypes->flatMap(
                fn($ut) => $ut->units->pluck('id')
            )
        );

        // Load all bookings in the 30-day window
        $bookings = Booking::with(['unit'])
            ->whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in', '<', $endDate->copy()->addDay()->toDateString())
            ->where('check_out', '>', $startDate->toDateString())
            ->get();

        // Index bookings by unit_id for fast lookup
        $bookingsByUnit = $bookings->groupBy('unit_id');

        // Shape data — units carry their bookings for the window
        $buildings->each(function ($building) use ($bookingsByUnit) {
            $building->unitTypes->each(function ($unitType) use ($bookingsByUnit) {
                $unitType->units->each(function ($unit) use ($bookingsByUnit) {
                    $unit->bookings = ($bookingsByUnit->get($unit->id) ?? collect())
                        ->map(fn($b) => [
                            'id'             => $b->id,
                            'reference'      => $b->booking_reference,
                            'guest_name'     => $b->guest_name,
                            'guest_phone'    => $b->guest_phone,
                            'check_in'       => $b->check_in->toDateString(),
                            'check_out'      => $b->check_out->toDateString(),
                            'nights'         => $b->nights,
                            'status'         => $b->status,
                            'payment_status' => $b->payment_status,
                            'total_amount'   => $b->total_amount,
                        ])->values();
                });
            });
        });

        $allBuildings = Building::where('is_active', true)
            ->when(! $user->hasGlobalAccess(), fn($q) => $q->whereIn('id', $user->accessibleBuildingIds()))
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/Availability/Index', [
            'buildings'    => $buildings,
            'allBuildings' => $allBuildings,
            'startDate'    => $startDate->toDateString(),
            'days'         => $days,
            'filters'      => [
                'building_id' => $request->building_id,
                'start'       => $startDate->toDateString(),
            ],
        ]);
    }
}
