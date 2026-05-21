<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Http\Request;
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

    public function store(Request $request, Building $building)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bedroom_type' => 'required|in:2-bed,3-bed,4-bed',
            'max_guests' => 'required|integer|min:1|max:20',
            'base_price_per_night' => 'required|numeric|min:0',
            'cleaning_fee' => 'required|numeric|min:0',
            'service_charge_percent' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'specific_amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['building_id'] = $building->id;
        $validated['slug'] = Str::slug($validated['name']);

        UnitType::create($validated);

        return redirect()->route('admin.properties.index')
            ->with('success', 'Unit type created successfully!');
    }

    public function update(Request $request, Building $building, UnitType $unitType)
    {
        // Ensure unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bedroom_type' => 'required|in:2-bed,3-bed,4-bed',
            'max_guests' => 'required|integer|min:1|max:20',
            'base_price_per_night' => 'required|numeric|min:0',
            'cleaning_fee' => 'required|numeric|min:0',
            'service_charge_percent' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'specific_amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $unitType->update($validated);

        return redirect()->route('admin.properties.index')
            ->with('success', 'Unit type updated successfully!');

    }

    public function destroy(Building $building, UnitType $unitType)
    {
        // Ensure unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        // Check if unit type has bookings
        if ($unitType->bookings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete unit type with existing bookings.');
        }

        $unitType->delete();

        return redirect()->route('admin.properties.index')
            ->with('success', 'Unit type deleted successfully!');
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

        // Get total units available for this type
        $totalUnits = $unitType->units()
            ->where('status', 'available')
            ->where('is_available', true)
            ->count();

        if ($totalUnits === 0) {
            return response()->json(['unavailable' => []]);
        }

        // Look 12 months ahead
        $start = now()->toDateString();
        $end   = now()->addMonths(12)->toDateString();

        // Get all active bookings in the window
        $bookings = Booking::whereHas('unit', fn($q) => $q->where('unit_type_id', $unitType->id))
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in', '<', $end)
            ->where('check_out', '>', $start)
            ->get(['check_in', 'check_out', 'unit_id']);

        // Get all blocked dates in the window
        $blocked = \App\Models\BlockedDate::whereHas('unit', fn($q) => $q->where('unit_type_id', $unitType->id))
            ->where('blocked_from', '<', $end)
            ->where('blocked_to', '>', $start)
            ->get(['blocked_from', 'blocked_to', 'unit_id']);

        // For each date, count how many units are occupied
        // A date is unavailable when occupied units >= total units
        $unavailable = [];
        $current = new \DateTime($start);
        $endDt   = new \DateTime($end);

        while ($current <= $endDt) {
            $dateStr  = $current->format('Y-m-d');
            $occupied = collect();

            foreach ($bookings as $booking) {
                if ($booking->check_in <= $dateStr && $booking->check_out > $dateStr) {
                    $occupied->push($booking->unit_id);
                }
            }

            foreach ($blocked as $block) {
                if ($block->blocked_from <= $dateStr && $block->blocked_to > $dateStr) {
                    $occupied->push($block->unit_id);
                }
            }

            if ($occupied->unique()->count() >= $totalUnits) {
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
