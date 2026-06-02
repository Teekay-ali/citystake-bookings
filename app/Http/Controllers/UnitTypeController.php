<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UnitTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $buildings = Building::where('is_active', true)
            ->with([
                'primaryImage',
                'unitTypes' => fn($q) => $q->where('is_active', true)
                    ->orderBy('base_price_per_night'),
            ])
            ->withCount(['unitTypes', 'units'])
            ->get();

        return Inertia::render('Properties/Index', [
            'buildings' => $buildings,
        ]);
    }

    public function show(Building $building, UnitType $unitType): Response
    {
        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        $unitType->load(['building.images', 'images', 'primaryImage']);

        $userBooking = null;

        if (auth()->check()) {
            $userBooking = Booking::where('user_id', auth()->id())
                ->where('unit_type_id', $unitType->id)
                ->whereNotIn('status', ['cancelled'])
                ->latest()
                ->first(['id', 'booking_reference', 'status', 'payment_status', 'check_in', 'check_out']);
        }

        $similarProperties = UnitType::with(['primaryImage', 'building'])
            ->where('is_active', true)
            ->where('id', '!=', $unitType->id)
            ->where(function ($q) use ($unitType) {
                $q->where('building_id', $unitType->building_id)
                    ->orWhere('bedroom_type', $unitType->bedroom_type);
            })
            ->limit(3)
            ->get(['id', 'building_id', 'name', 'slug', 'bedroom_type', 'max_guests', 'base_price_per_night']);

        // Inherit building images if unit type has none
        $effectiveImages = $unitType->images->isNotEmpty()
            ? $unitType->images
            : $unitType->building->images;

        return Inertia::render('Properties/Show', [
            'building'          => $building,
            'unitType'          => array_merge($unitType->toArray(), [
                'images' => $effectiveImages->values(),
            ]),
            'userBooking'       => $userBooking,
            'similarProperties' => $similarProperties,
        ]);
    }

    public function checkAvailability(Request $request, Building $building, UnitType $unitType)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            return response()->json([
                'available' => false,
                'message' => 'Invalid unit type for this building'
            ], 400);
        }

        $isAvailable = $unitType->hasAvailability(
            $request->check_in,
            $request->check_out
        );

        $availableCount = $unitType->getAvailableUnitsCount(
            $request->check_in,
            $request->check_out
        );

        return response()->json([
            'available' => $isAvailable,
            'available_units' => $availableCount,
            'message' => $isAvailable
                ? "Available! We have {$availableCount} unit(s) for your dates"
                : 'No units available for selected dates'
        ]);
    }

    public function unavailableDates(Request $request, Building $building, UnitType $unitType)
    {
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        $totalUnits = $unitType->units()
            ->where('status', 'available')
            ->where('is_available', true)
            ->count();

        if ($totalUnits === 0) {
            return response()->json(['unavailable' => []]);
        }

        $start = now()->toDateString();
        $end   = now()->addMonths(12)->toDateString();

        // Get unit IDs for this type
        $unitIds = $unitType->units()->pluck('id');

        // Collect all occupied ranges per unit (bookings + blocked dates)
        $ranges = collect();

        DB::table('bookings')
            ->whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled', 'paused'])
            ->where('check_in', '<', $end)
            ->where('check_out', '>', $start)
            ->whereNull('deleted_at')
            ->select('unit_id', 'check_in as from', 'check_out as to')
            ->get()
            ->each(fn($r) => $ranges->push($r));

        DB::table('blocked_dates')
            ->whereIn('unit_id', $unitIds)
            ->where('blocked_from', '<', $end)
            ->where('blocked_to', '>', $start)
            ->select('unit_id', 'blocked_from as from', 'blocked_to as to')
            ->get()
            ->each(fn($r) => $ranges->push($r));

        // Group ranges by unit_id for fast lookup
        $byUnit = $ranges->groupBy('unit_id');

        // Walk each day — but skip days where even one unit is free
        $unavailable = [];
        $current     = new \DateTime($start);
        $endDt       = new \DateTime($end);

        while ($current <= $endDt) {
            $dateStr      = $current->format('Y-m-d');
            $occupiedUnits = [];

            foreach ($byUnit as $unitId => $unitRanges) {
                foreach ($unitRanges as $range) {
                    if ($range->from <= $dateStr && $range->to > $dateStr) {
                        $occupiedUnits[$unitId] = true;
                        break; // no need to check other ranges for this unit
                    }
                }
            }

            if (count($occupiedUnits) >= $totalUnits) {
                $unavailable[] = $dateStr;
            }

            $current->modify('+1 day');
        }

        return response()->json(['unavailable' => $unavailable]);
    }

    public function showBuilding(Building $building): Response
    {
        if (! $building->is_active) {
            abort(404);
        }

        $building->load([
            'images',
            'unitTypes' => fn($q) => $q->where('is_active', true)
                ->with(['primaryImage'])
                ->withCount('units')
                ->orderBy('base_price_per_night'),
        ]);

        // Available unit count per unit type for today
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $building->unitTypes->each(function ($unitType) use ($today, $tomorrow) {
            $unitType->available_now = $unitType->getAvailableUnitsCount($today, $tomorrow);
        });

        // Other buildings for "explore more" section
        $otherBuildings = Building::where('is_active', true)
            ->where('id', '!=', $building->id)
            ->with(['images' => fn($q) => $q->where('is_primary', true)])
            ->withCount('unitTypes')
            ->get(['id', 'name', 'slug', 'city', 'address']);

        // Active bookings for this building's unit types — guest-specific, not cached
        $userBuildingBookings = [];
        if (auth()->check()) {
            $unitTypeIds = $building->unitTypes->pluck('id');
            $userBuildingBookings = Booking::where('user_id', auth()->id())
                ->whereIn('unit_type_id', $unitTypeIds)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->latest()
                ->get(['id', 'booking_reference', 'unit_type_id', 'status', 'payment_status', 'check_in', 'check_out'])
                ->toArray();
        }

        return Inertia::render('Properties/Building', [
            'building'            => $building,
            'otherBuildings'      => $otherBuildings,
            'userBuildingBookings' => $userBuildingBookings,
        ]);
    }

}
