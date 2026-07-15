<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $query = Organization::withCount('bookings')->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact_phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $query->paginate(20)->withQueryString(),
            'filters'       => $request->only(['search', 'status']),
        ]);
    }

    // Lightweight list for the booking form's "bill to organization" picker.
    public function options()
    {
        abort_unless(auth()->user()->can('create-bookings'), 403);

        return response()->json(
            Organization::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'contact_name', 'contact_phone'])
        );
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-bookings'), 403);

        $validated = $this->rules($request);

        $org = Organization::create([...$validated, 'created_by' => auth()->id()]);

        AuditLog::log('organization.created', $org, null, ['name' => $org->name]);

        // Support inline creation from the booking form (expects JSON).
        if ($request->wantsJson()) {
            return response()->json($org->only('id', 'name', 'contact_name', 'contact_phone'));
        }

        return redirect()->route('manage.organizations.index')
            ->with('success', 'Organization added.');
    }

    public function show(Organization $organization)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $organization->load(['bookings' => fn ($q) => $q->with('building')->latest()->limit(50)]);

        return Inertia::render('Admin/Organizations/Show', [
            'organization' => $organization,
            'totalSpend'   => (float) $organization->bookings()->sum('total_amount'),
            'bookingCount' => $organization->bookings()->count(),
        ]);
    }

    public function update(Request $request, Organization $organization)
    {
        abort_unless(auth()->user()->can('manage-bookings'), 403);

        $validated = $this->rules($request, true);
        $organization->update($validated);

        AuditLog::log('organization.updated', $organization, null, ['name' => $organization->name]);

        return back()->with('success', 'Organization updated.');
    }

    public function destroy(Organization $organization)
    {
        abort_unless(auth()->user()->can('manage-bookings'), 403);

        if ($organization->bookings()->exists()) {
            return back()->with('error', 'Cannot delete an organization with bookings. Deactivate it instead.');
        }

        AuditLog::log('organization.deleted', $organization, ['name' => $organization->name], null);
        $organization->delete();

        return back()->with('success', 'Organization removed.');
    }

    private function rules(Request $request, bool $withActive = false): array
    {
        return $request->validate(array_merge([
            'name'          => 'required|string|max:255',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'address'       => 'nullable|string|max:500',
            'notes'         => 'nullable|string|max:1000',
        ], $withActive ? ['is_active' => 'boolean'] : []));
    }
}
