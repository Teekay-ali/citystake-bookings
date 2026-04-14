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

        $date = $request->date ? Carbon::parse($request->date) : Carbon::today();

        $buildingsQuery = Building::with([
            'unitTypes.units' => function ($q) {
                $q->orderBy('unit_number');
            },
        ])->where('is_active', true);

        // Building scope: staff only see their buildings
        $user = auth()->user();
        if (!$user->hasGlobalAccess()) {
            $buildingIds = $user->accessibleBuildingIds();
            $buildingsQuery->whereIn('id', $buildingIds);
        }

        if ($request->building_id) {
            $buildingsQuery->where('id', $request->building_id);
        }

        $buildings = $buildingsQuery->get();

        // Collect all unit IDs across filtered buildings
        $unitIds = $buildings->flatMap(
            fn($b) => $b->unitTypes->flatMap(
                fn($ut) => $ut->units->pluck('id')
            )
        );

        // Get all bookings that overlap with the selected date
        $bookings = Booking::with(['unit', 'unitType'])
            ->whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in', '<=', $date->toDateString())
            ->where('check_out', '>', $date->toDateString())
            ->get()
            ->keyBy('unit_id'); // one active booking per unit per day

        // Shape units with their booking status for the frontend
        $buildings->each(function ($building) use ($bookings, $date) {
            $building->unitTypes->each(function ($unitType) use ($bookings, $date) {
                $unitType->units->each(function ($unit) use ($bookings, $date) {
                    $booking = $bookings->get($unit->id);

                    $unit->availability = $this->resolveUnitStatus($unit, $booking);
                    $unit->current_booking = $booking ? [
                        'id' => $booking->id,
                        'reference' => $booking->booking_reference,
                        'guest_name' => $booking->guest_name,
                        'guest_phone' => $booking->guest_phone,
                        'check_in' => $booking->check_in->toDateString(),
                        'check_out' => $booking->check_out->toDateString(),
                        'nights' => $booking->nights,
                        'status' => $booking->status,
                        'payment_status' => $booking->payment_status,
                        'total_amount' => $booking->total_amount,
                    ] : null;
                });
            });
        });

        // All buildings for the filter dropdown (unscoped)
        $allBuildings = Building::where('is_active', true)
            ->when(!$user->hasGlobalAccess(), function ($q) use ($user) {
                $q->whereIn('id', $user->accessibleBuildingIds());
            })
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/Availability/Index', [
            'buildings' => $buildings,
            'allBuildings' => $allBuildings,
            'selectedDate' => $date->toDateString(),
            'filters' => [
                'building_id' => $request->building_id,
                'date' => $date->toDateString(),
            ],
            'summary' => [
                'total' => $unitIds->count(),
                'occupied' => $bookings->count(),
                'available' => $unitIds->count() - $bookings->count(),
                'maintenance' => $buildings->flatMap(
                    fn($b) => $b->unitTypes->flatMap(
                        fn($ut) => $ut->units->where('status', 'maintenance')
                    )
                )->count(),
            ],
        ]);
    }

    private function resolveUnitStatus($unit, $booking): string
    {
        if ($unit->status === 'maintenance') return 'maintenance';
        if ($unit->status === 'retired') return 'retired';
        if (!$booking) return 'available';
        if ($booking->status === 'checked_in') return 'checked_in';
        return 'occupied'; // confirmed but not yet checked in
    }
}
