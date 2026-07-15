<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\HomeController;
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
        abort_unless(auth()->user()->can('view-properties'), 403);

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
        abort_unless(auth()->user()->can('create-properties'), 403);

        return Inertia::render('Admin/Properties/CreateBuilding');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('create-properties'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'description' => 'nullable|string',
            'caution_fee_amount' => 'required|numeric|min:0',
            'amenities' => 'nullable|array',
            'monthly_emergency_limit' => 'required|numeric|min:0',
            'standard_checkout_time'    => 'required|date_format:H:i',
            'late_checkout_fee_per_hour'=> 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $building = Building::create($validated);

        AuditLog::log('building.created', $building, null, ['name' => $building->name]);

        HomeController::clearPropertyCache();

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property created successfully!');
    }

    public function edit(Building $building)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

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
            'caution_fee_amount' => 'required|numeric|min:0',
            'amenities' => 'nullable|array',
            'monthly_emergency_limit' => 'required|numeric|min:0',
            'standard_checkout_time'    => 'required|date_format:H:i',
            'late_checkout_fee_per_hour'=> 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $building->update($validated);

        AuditLog::log('building.updated', $building, ['name' => $building->getOriginal('name')], ['name' => $building->name]);

        HomeController::clearPropertyCache();

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property updated successfully!');
    }

    // ── Property policy (versioned rich text) ──

    public function editPolicy(Building $building)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $current = $building->currentPolicy;

        return Inertia::render('Admin/Properties/EditPolicy', [
            'building' => ['id' => $building->id, 'name' => $building->name, 'slug' => $building->slug],
            'policy'   => $current ? [
                'body'       => $current->body,
                'version'    => $current->version,
                'updated_at' => $current->updated_at,
            ] : null,
        ]);
    }

    public function updatePolicy(Request $request, Building $building)
    {
        abort_unless(auth()->user()->can('manage-properties'), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:50000',
        ]);

        $body = $this->sanitizePolicy($validated['body']);

        // Each save publishes a new immutable version so historical bookings can
        // still point at exactly the policy that applied to them.
        $nextVersion = (int) ($building->policies()->max('version') ?? 0) + 1;

        $policy = $building->policies()->create([
            'version'    => $nextVersion,
            'body'       => $body,
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('building.policy_updated', $building, null, ['version' => $policy->version]);

        return back()->with('success', "Policy saved as version {$policy->version}.");
    }

    private function sanitizePolicy(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,h2,h3,h4,a[href],blockquote');
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('HTML.TargetBlank', true);
        $config->set('Cache.DefinitionImpl', null);

        return (new \HTMLPurifier($config))->purify($html);
    }

    public function destroy(Building $building)
    {
        abort_unless(auth()->user()->can('create-properties'), 403);

        // Check if building has bookings
        if ($building->bookings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete property with existing bookings.');
        }

        AuditLog::log('building.deleted', $building, ['name' => $building->name], null);

        $building->delete();

        HomeController::clearPropertyCache();

        return redirect()->route('manage.properties.index')
            ->with('success', 'Property deleted successfully!');
    }

}
