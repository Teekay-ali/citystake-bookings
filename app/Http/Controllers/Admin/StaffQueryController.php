<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\StaffQuery;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffQueryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Only managers and super admins
        abort_unless($user->can('manage-staff-queries'), 403);

        $query = StaffQuery::with(['staff', 'issuedBy', 'building'])
            ->latest();

        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        if ($request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        $queries = $query->paginate(20)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        // Staff members scoped to accessible buildings
        $staffMembers = User::whereHas('buildings', function ($q) use ($user) {
            if (!$user->hasGlobalAccess()) {
                $q->whereIn('buildings.id', $user->accessibleBuildingIds());
            }
        })->where('is_staff', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/StaffQueries/Index', [
            'queries'      => $queries,
            'buildings'    => $buildings,
            'staffMembers' => $staffMembers,
            'types'        => StaffQuery::types(),
            'filters'      => $request->only(['building_id', 'status', 'type', 'staff_id']),
            'counts'       => [
                'open'      => StaffQuery::where('status', 'open')->count(),
                'responded' => StaffQuery::where('status', 'responded')->count(),
            ],
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        abort_unless($user->can('manage-staff-queries'), 403);

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->where('is_active', true)->get(['id', 'name']);

        $staffMembers = User::whereHas('buildings', function ($q) use ($user) {
            if (!$user->hasGlobalAccess()) {
                $q->whereIn('buildings.id', $user->accessibleBuildingIds());
            }
        })->where('is_staff', true)
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/StaffQueries/Create', [
            'buildings'    => $buildings,
            'staffMembers' => $staffMembers,
            'types'        => StaffQuery::types(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->can('manage-staff-queries'), 403);

        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'staff_id'    => 'required|exists:users,id',
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:3000',
            'type'        => 'required|in:' . implode(',', array_keys(StaffQuery::types())),
        ]);

        StaffQuery::create([
            ...$validated,
            'issued_by' => $user->id,
        ]);

        return redirect()->route('manage.staff-queries.index')
            ->with('success', 'Staff query recorded successfully.');
    }

    public function show(StaffQuery $staffQuery)
    {
        $this->authorize($staffQuery);

        $staffQuery->load(['staff', 'issuedBy', 'building']);

        return Inertia::render('Admin/StaffQueries/Show', [
            'query' => $staffQuery,
            'types' => StaffQuery::types(),
        ]);
    }

    public function resolve(Request $request, StaffQuery $staffQuery)
    {
        $this->authorize($staffQuery);

        $validated = $request->validate([
            'resolution' => 'required|string|max:1000',
        ]);

        $staffQuery->update([
            'status'     => 'closed',
            'resolution' => $validated['resolution'],
            'closed_at'  => now(),
        ]);

        return back()->with('success', 'Query closed.');
    }

    public function destroy(StaffQuery $staffQuery)
    {
        $this->authorize($staffQuery);
        $staffQuery->delete();

        return redirect()->route('manage.staff-queries.index')
            ->with('success', 'Query deleted.');
    }

    private function authorize(StaffQuery $staffQuery): void
    {
        $user = auth()->user();

        abort_unless($user->can('manage-staff-queries'), 403);

        if (!$user->hasGlobalAccess() &&
            !in_array($staffQuery->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}
