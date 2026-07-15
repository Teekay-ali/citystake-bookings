<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\PropertyPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PropertyPolicyController extends Controller
{
    // Public-facing property policy page. Optional ?v= pins a specific version
    // (used by booking-confirmation emails so a guest sees exactly what applied).
    public function show(Request $request, Building $building)
    {
        abort_unless($building->is_active, 404);

        $query = PropertyPolicy::where('building_id', $building->id);

        $policy = $request->filled('v')
            ? (clone $query)->where('version', (int) $request->v)->first()
                ?? $query->orderByDesc('version')->first()
            : $query->orderByDesc('version')->first();

        return Inertia::render('Properties/Policy', [
            'building' => [
                'name'    => $building->name,
                'city'    => $building->city,
                'address' => $building->address,
                'slug'    => $building->slug,
            ],
            'policy' => $policy ? [
                'body'       => $policy->body,
                'version'    => $policy->version,
                'updated_at' => $policy->updated_at,
            ] : null,
        ]);
    }
}
