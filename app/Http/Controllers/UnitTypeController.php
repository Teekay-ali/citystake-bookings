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
        $query = UnitType::with(['building', 'primaryImage'])
            ->select('id', 'building_id', 'name', 'slug', 'bedroom_type', 'max_guests', 'base_price_per_night', 'is_active', 'created_at')
            ->where('is_active', true)
            ->whereHas('building', function ($q) {
                $q->where('is_active', true);
            });

        // Filter by bedroom type
        if ($request->filled('bedroom_type')) {
            $query->where('bedroom_type', $request->bedroom_type);
        }

        // Filter by max guests
        if ($request->filled('guests')) {
            $query->where('max_guests', '>=', $request->guests);
        }

        // Filter by building
        if ($request->filled('building')) {
            $query->where('building_id', $request->building);
        }

        // Sort by price
        if ($request->filled('sort')) {
            if ($request->sort === 'price_asc') {
                $query->orderBy('base_price_per_night', 'asc');
            } elseif ($request->sort === 'price_desc') {
                $query->orderBy('base_price_per_night', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $unitTypes = $query->paginate(9)->withQueryString();

        // Get all buildings for filter
        $buildings = Building::where('is_active', true)
            ->select('id', 'name')
            ->get();

        return Inertia::render('Properties/Index', [
            'unitTypes' => $unitTypes,
            'buildings' => $buildings,
            'filters' => [
                'bedroom_type' => $request->bedroom_type,
                'guests' => $request->guests,
                'building' => $request->building,
                'sort_by' => $request->sort,
            ],
        ]);
    }

    public function show(Building $building, UnitType $unitType): Response
    {
        $unitType->load(['building', 'images', 'primaryImage']);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

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

        return Inertia::render('Properties/Show', [
            'building'           => $building,
            'unitType'           => $unitType,
            'userBooking'        => $userBooking,
            'similarProperties'  => $similarProperties,
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

}
