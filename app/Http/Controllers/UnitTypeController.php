<?php

namespace App\Http\Controllers;

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

        return Inertia::render('Properties/Show', [
            'building' => $building,
            'unitType' => $unitType,
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
}
