<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Traits\ScopedByBuilding;
use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BuildingController extends Controller
{
    use ScopedByBuilding;

    public function index()
    {
        $user = auth()->user();
        $buildings = Building::with(['unitTypes' => function ($query) {
            $query->withCount('units');
        }])
            ->withCount(['unitTypes', 'units'])
            ->when(!$user->hasGlobalAccess(), function ($q) use ($user) {
                $q->whereIn('id', $user->accessibleBuildingIds() ?? []);
            })
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
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $building = Building::create($validated);

        AuditLog::log('building.created', $building, null, ['name' => $building->name]);

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property created successfully!');
    }

    public function edit(Building $building)
    {
        $building->load(['images' => fn($q) => $q->orderBy('sort_order')]);

        return Inertia::render('Admin/Properties/EditBuilding', [
            'building' => $building,
        ]);
    }

    public function update(Request $request, Building $building)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

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

        AuditLog::log('building.updated', $building, ['name' => $building->getOriginal('name')], ['name' => $building->name]);

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property updated successfully!');
    }

    public function destroy(Building $building)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        // Check if building has bookings
        if ($building->bookings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete property with existing bookings.');
        }

        AuditLog::log('building.deleted', $building, ['name' => $building->name], null);

        $building->delete();

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property deleted successfully!');
    }

}
