<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\UnitType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class UnitTypeController extends Controller
{
    public function create(Building $building)
    {
        return Inertia::render('Admin/Properties/CreateUnitType', [
            'building' => $building,
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

    public function edit(Building $building, UnitType $unitType)
    {
        // Ensure unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        return Inertia::render('Admin/Properties/EditUnitType', [
            'building' => $building,
            'unitType' => $unitType,
        ]);
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
}
