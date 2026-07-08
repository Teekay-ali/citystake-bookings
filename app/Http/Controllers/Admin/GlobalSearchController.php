<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(auth()->user()->can('view-bookings'), 403);

        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess() ? null : $user->accessibleBuildingIds();

        // Bookings — by reference or guest name
        $bookings = Booking::with('building:id,name')
            ->when($buildingIds, fn($q) => $q->whereIn('building_id', $buildingIds))
            ->where(function ($q) use ($query) {
                $q->where('booking_reference', 'like', "%{$query}%")
                    ->orWhere('guest_name', 'like', "%{$query}%")
                    ->orWhere('guest_email', 'like', "%{$query}%")
                    ->orWhere('guest_phone', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'building_id', 'booking_reference', 'guest_name', 'status', 'check_in', 'check_out'])
            ->map(fn($b) => [
                'type'     => 'booking',
                'id'       => $b->id,
                'label'    => $b->booking_reference,
                'sublabel' => $b->guest_name . ' · ' . $b->building?->name,
                'status'   => $b->status,
                'url'      => route('manage.bookings.show', $b->booking_reference),
            ]);

        // Units — by unit number
        $units = Unit::with('unitType.building')
            ->when($buildingIds, fn($q) => $q->whereHas('unitType.building', fn($b) => $b->whereIn('id', $buildingIds)))
            ->where('unit_number', 'like', "%{$query}%")
            ->limit(3)
            ->get()
            ->map(fn($u) => [
                'type'     => 'unit',
                'id'       => $u->id,
                'label'    => $u->unit_number,
                'sublabel' => $u->unitType?->name . ' · ' . $u->unitType?->building?->name,
                'status'   => $u->status,
                'url'      => route('manage.availability.index'),
            ]);

        // Staff/guests — by name or email, scoped to accessible buildings for non-global users
        $users = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->when($buildingIds, function ($q) use ($buildingIds) {
                $q->where(function ($inner) use ($buildingIds) {
                    // Guests with bookings in accessible buildings
                    $inner->whereHas('bookings', fn($b) => $b->whereIn('building_id', $buildingIds))
                        // Staff assigned to accessible buildings
                        ->orWhereHas('buildings', fn($b) => $b->whereIn('buildings.id', $buildingIds));
                });
            })
            ->limit(3)
            ->get(['id', 'name', 'email', 'is_staff', 'is_admin'])
            ->map(fn($u) => [
                'type'     => 'user',
                'id'       => $u->id,
                'label'    => $u->name,
                'sublabel' => $u->email,
                'status'   => $u->is_staff || $u->is_admin ? 'staff' : 'guest',
                'url'      => $u->is_staff || $u->is_admin
                    ? route('manage.staff.index')
                    : route('manage.bookings.index'),
            ]);

        return response()->json(
            $bookings->concat($units)->concat($users)->values()
        );
    }
}
