<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Building;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BlockedDateController extends Controller
{
    public function index(Request $request)
    {
        $query = BlockedDate::with(['unit.unitType.building', 'creator']); // Changed this line

        // Filter by building - need to go through unit_type
        if ($request->building) {
            $query->whereHas('unit.unitType', function ($q) use ($request) {
                $q->where('building_id', $request->building);
            });
        }

        // Filter by status (upcoming, active, past)
        if ($request->status === 'upcoming') {
            $query->where('blocked_from', '>', now());
        } elseif ($request->status === 'active') {
            $query->where('blocked_from', '<=', now())
                ->where('blocked_to', '>=', now());
        } elseif ($request->status === 'past') {
            $query->where('blocked_to', '<', now());
        }

        $blockedDates = $query->latest()->paginate(20)->withQueryString();

        $buildings = Building::select('id', 'name')->get();

        return Inertia::render('Admin/BlockedDates/Index', [
            'blockedDates' => $blockedDates,
            'buildings' => $buildings,
            'filters' => [
                'building' => $request->building,
                'status' => $request->status,
            ],
        ]);
    }

    public function create()
    {
        $buildings = Building::with(['units' => function ($q) {
            $q->select('id', 'unit_type_id', 'unit_number')
                ->with(['unitType:id,name,bedroom_type']);
        }])->where('is_active', true)->select('id', 'name')->get();

        return Inertia::render('Admin/BlockedDates/Create', [
            'buildings' => $buildings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'blocked_from' => 'required|date',
            'blocked_to' => 'required|date|after_or_equal:blocked_from',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check for overlapping bookings
        $unit = Unit::findOrFail($validated['unit_id']);

        $hasBookings = $unit->bookings()
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($validated) {
                $query->where('check_in', '<=', $validated['blocked_to'])
                    ->where('check_out', '>=', $validated['blocked_from']);
            })
            ->exists();

        if ($hasBookings) {
            return redirect()->back()
                ->with('error', 'Cannot block dates - there are existing bookings during this period.')
                ->withInput();
        }

        // Check for overlapping blocked dates
        $hasOverlap = $unit->blockedDates()
            ->where('blocked_from', '<=', $validated['blocked_to'])
            ->where('blocked_to', '>=', $validated['blocked_from'])
            ->exists();

        if ($hasOverlap) {
            return redirect()->back()
                ->with('error', 'Cannot block dates - this period overlaps with existing blocked dates.')
                ->withInput();
        }

        BlockedDate::create([
            'unit_id' => $validated['unit_id'],
            'blocked_from' => $validated['blocked_from'],
            'blocked_to' => $validated['blocked_to'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.blocked-dates.index')
            ->with('success', 'Dates blocked successfully!');
    }

    public function destroy(BlockedDate $blockedDate)
    {
        $blockedDate->delete();

        return redirect()->route('admin.blocked-dates.index')
            ->with('success', 'Blocked dates removed successfully!');
    }
}
