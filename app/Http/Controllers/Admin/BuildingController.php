<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::with(['unitTypes' => function ($query) {
            $query->withCount('units');
        }])
            ->withCount(['unitTypes', 'units'])
            ->latest()
            ->get();


        return Inertia::render('Admin/Properties/Index', [
            'buildings' => $buildings,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Properties/CreateBuilding');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Building::create($validated);

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property created successfully!');
    }

    public function edit(Building $building)
    {
        return Inertia::render('Admin/Properties/EditBuilding', [
            'building' => $building,
        ]);
    }

    public function update(Request $request, Building $building)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $building->update($validated);

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property updated successfully!');
    }

    public function destroy(Building $building)
    {
        // Check if building has bookings
        if ($building->bookings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete property with existing bookings.');
        }

        $building->delete();

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property deleted successfully!');
    }

}
