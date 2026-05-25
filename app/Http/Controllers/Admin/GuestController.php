<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(auth()->user()->can('manage-guests'), 403);

        $query = User::where('is_staff', false)
            ->where('is_admin', false)
            ->withCount('bookings')
            ->withSum('bookings', 'total_amount')
            ->with(['bookings' => fn($q) => $q->latest()->limit(1)->select('id', 'user_id', 'status', 'check_in', 'check_out')])
            ->when($request->search, fn($q) => $q->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->when($request->status === 'active', fn($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total'    => User::where('is_staff', false)->where('is_admin', false)->count(),
            'active'   => User::where('is_staff', false)->where('is_admin', false)->where('is_active', true)->count(),
            'inactive' => User::where('is_staff', false)->where('is_admin', false)->where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Guests/Index', [
            'guests'  => $query,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(User $guest): Response
    {
        abort_unless(auth()->user()->can('manage-guests'), 403);
        abort_unless(!$guest->is_staff && !$guest->is_admin, 404);

        $bookings = Booking::where('user_id', $guest->id)
            ->with(['building:id,name', 'unitType:id,name', 'unit:id,unit_number'])
            ->latest()
            ->get(['id', 'booking_reference', 'building_id', 'unit_type_id', 'unit_id',
                'status', 'payment_status', 'check_in', 'check_out', 'total_amount',
                'nights', 'created_at']);

        $stats = [
            'total_bookings'  => $bookings->count(),
            'total_spend'     => $bookings->where('payment_status', 'paid')->sum('total_amount'),
            'completed_stays' => $bookings->whereIn('status', ['completed', 'checked_out'])->count(),
            'cancelled'       => $bookings->where('status', 'cancelled')->count(),
            'last_stay'       => $bookings->whereIn('status', ['completed', 'checked_out'])->sortByDesc('check_out')->first()?->check_out,
        ];

        return Inertia::render('Admin/Guests/Show', [
            'guest'    => $guest,
            'bookings' => $bookings,
            'stats'    => $stats,
        ]);
    }

    public function toggleActive(User $guest)
    {
        abort_unless(auth()->user()->can('manage-guests'), 403);
        abort_unless(!$guest->is_staff && !$guest->is_admin, 404);

        $guest->update(['is_active' => !$guest->is_active]);

        AuditLog::log(
            $guest->is_active ? 'guest.activated' : 'guest.deactivated',
            $guest, null,
            ['is_active' => $guest->is_active]
        );

        return back()->with('success', $guest->is_active
            ? "{$guest->name}'s account activated."
            : "{$guest->name}'s account deactivated."
        );
    }
}
