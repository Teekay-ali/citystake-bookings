<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BookingEnquiry;
use App\Models\Building;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $user = auth()->user();

        $query = BookingEnquiry::with(['building', 'unitType', 'handledBy'])
            ->scopedToUser($user)
            ->latest();

        if ($request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('guest_name', 'like', "%{$request->search}%")
                    ->orWhere('guest_email', 'like', "%{$request->search}%")
                    ->orWhere('guest_phone', 'like', "%{$request->search}%");
            });
        }

        $enquiries = $query->paginate(15)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), fn ($q) => $q->whereIn('id', $user->accessibleBuildingIds()))
            ->get(['id', 'name']);

        $counts = BookingEnquiry::scopedToUser($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Enquiries/Index', [
            'enquiries' => $enquiries,
            'buildings' => $buildings,
            'filters'   => $request->only(['building_id', 'status', 'search']),
            'counts'    => [
                'new'       => (int) ($counts['new']       ?? 0),
                'contacted' => (int) ($counts['contacted'] ?? 0),
                'converted' => (int) ($counts['converted'] ?? 0),
                'closed'    => (int) ($counts['closed']    ?? 0),
            ],
        ]);
    }

    public function show(BookingEnquiry $enquiry)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);
        $this->authorizeBuilding($enquiry);

        $enquiry->load(['building', 'unitType', 'handledBy', 'convertedBooking']);

        return Inertia::render('Admin/Enquiries/Show', [
            'enquiry' => array_merge($enquiry->toArray(), [
                'nights'       => $enquiry->nights(),
                'status_label' => $enquiry->statusLabel(),
            ]),
        ]);
    }

    public function updateStatus(Request $request, BookingEnquiry $enquiry)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);
        $this->authorizeBuilding($enquiry);

        $validated = $request->validate([
            'status'      => 'required|in:new,contacted,converted,closed',
            'staff_notes' => 'nullable|string|max:1000',
        ]);

        $enquiry->update([
            'status'      => $validated['status'],
            'staff_notes' => $validated['staff_notes'] ?? $enquiry->staff_notes,
            'handled_by'  => auth()->id(),
        ]);

        AuditLog::log('enquiry.status_updated', $enquiry, null, ['status' => $validated['status']]);

        return back()->with('success', 'Enquiry updated.');
    }

    /**
     * Mark the enquiry converted and redirect to the prefilled admin booking form.
     */
    public function convert(BookingEnquiry $enquiry)
    {
        abort_unless(auth()->user()->can('create-bookings'), 403);
        $this->authorizeBuilding($enquiry);

        $enquiry->update([
            'status'     => 'converted',
            'handled_by' => auth()->id(),
        ]);

        return redirect()->route('manage.bookings.create', [
            'building_id'      => $enquiry->building_id,
            'unit_type_id'     => $enquiry->unit_type_id,
            'check_in'         => $enquiry->check_in->toDateString(),
            'check_out'        => $enquiry->check_out->toDateString(),
            'nights'           => $enquiry->nights(),
            'guests'           => $enquiry->guests,
            'guest_name'       => $enquiry->guest_name,
            'guest_email'      => $enquiry->guest_email,
            'guest_phone'      => $enquiry->guest_phone,
            'special_requests' => $enquiry->special_requests,
        ]);
    }

    public function destroy(BookingEnquiry $enquiry)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);
        $this->authorizeBuilding($enquiry);

        AuditLog::log('enquiry.deleted', $enquiry, ['guest' => $enquiry->guest_name], null);
        $enquiry->delete();

        return redirect()->route('manage.enquiries.index')->with('success', 'Enquiry deleted.');
    }

    private function authorizeBuilding(BookingEnquiry $enquiry): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($enquiry->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}
