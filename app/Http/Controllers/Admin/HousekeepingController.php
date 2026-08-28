<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitTurnover;
use App\Notifications\UnitReadyForQaNotification;
use App\Services\NotificationService;
use App\Services\UnitTurnoverService;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class HousekeepingController extends Controller
{
    use ScopedByBuilding;

    private const OCCUPYING_STATUSES = ['confirmed', 'checked_in', 'paused'];

    public function __construct(private UnitTurnoverService $turnovers)
    {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('request-cleaning'), 403);

        $buildingIds = $this->scopedBuildingIds();
        $today       = Carbon::today();

        $buildings = $this->accessibleBuildings()->get(['id', 'name', 'standard_checkout_time', 'standard_checkin_time'])->keyBy('id');

        $units = Unit::whereHas('unitType', fn ($q) => $q->whereIn('building_id', $buildingIds))
            ->with('unitType:id,name,building_id')
            ->orderBy('unit_number')
            ->get();
        $unitIds = $units->pluck('id');

        // Current occupancy, the next arrival, and the last checkout per unit.
        $occupied = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', self::OCCUPYING_STATUSES)
            ->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)
            ->get(['id', 'unit_id', 'guest_name', 'check_out'])->keyBy('unit_id');

        $nextArrival = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDate('check_in', '>=', $today)
            ->orderBy('check_in')->get(['id', 'unit_id', 'guest_name', 'check_in'])
            ->groupBy('unit_id')->map->first();

        // A guest has departed when the booking was explicitly checked out
        // (checked_out_at) or its checkout date has passed. Latest departure wins.
        $lastCheckout = Booking::whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled'])
            ->where(fn ($q) => $q->whereNotNull('checked_out_at')->orWhereDate('check_out', '<=', $today))
            ->get(['id', 'unit_id', 'guest_name', 'check_out', 'checked_out_at'])
            ->groupBy('unit_id')
            ->map(fn ($g) => $g->sortByDesc(fn ($b) => $b->checked_out_at ?? $b->check_out)->first());

        $turnovers = UnitTurnover::whereIn('unit_id', $unitIds)->get()
            ->groupBy('unit_id')->map(fn ($g) => $g->sortByDesc('id')->first());

        $blocked = BlockedDate::whereIn('unit_id', $unitIds)
            ->whereDate('blocked_from', '<=', $today)->whereDate('blocked_to', '>=', $today)
            ->get()->keyBy('unit_id');

        $rows = $units->map(function ($u) use ($occupied, $nextArrival, $lastCheckout, $turnovers, $blocked, $buildings) {
            $bId      = $u->unitType->building_id;
            $building = $buildings->get($bId);
            $to       = $turnovers->get($u->id);
            $occ      = $occupied->get($u->id);
            $out      = $lastCheckout->get($u->id);
            $arrival  = $nextArrival->get($u->id);
            $active   = $to && in_array($to->status, UnitTurnover::ACTIVE_STATUSES, true);

            $departedAt = $out ? ($out->checked_out_at ?? $out->check_out) : null;
            $state = $this->turnovers->readinessState(
                (bool) $occ, (bool) $blocked->get($u->id), $to, $departedAt, $u->status === 'available'
            );

            return [
                'unit_id'      => $u->id,
                'unit_number'  => $u->unit_number,
                'unit_type'    => $u->unitType->name,
                'floor'        => $u->floor,
                'building_name' => $building?->name,
                'state'        => $state,
                'turnover_id'  => $active ? $to->id : null,
                'booking_id'   => $out?->id,
                'checkout'     => $out ? $this->fmt($out->check_out, $building?->standard_checkout_time) : null,
                'arrival'      => $arrival ? $this->fmt($arrival->check_in, $building?->standard_checkin_time) : null,
                'guest_out'    => $out?->guest_name,
                'guest_next'   => $arrival?->guest_name,
                // When the unit entered its current state — powers the "aging" label.
                'since'        => match ($state) {
                    'needs_cleaning' => $departedAt ? Carbon::parse($departedAt)->toISOString() : null,
                    'cleaning'       => $to?->cleaning_requested_at?->toISOString(),
                    'ready_for_qa'   => $to?->cleaning_completed_at?->toISOString(),
                    'qa_in_progress' => $to?->qa_started_at?->toISOString(),
                    default          => null,
                },
            ];
        })->values();

        return Inertia::render('Admin/Housekeeping/Index', [
            'units'     => $rows,
            'buildings' => $buildings->values(),
            'counts'    => $rows->countBy('state'),
        ]);
    }

    public function requestCleaning(Request $request)
    {
        abort_unless(auth()->user()->can('request-cleaning'), 403);

        $data = $request->validate([
            'unit_id'    => 'required|exists:units,id',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        $unit = Unit::with('unitType')->findOrFail($data['unit_id']);
        abort_unless(in_array($unit->unitType->building_id, $this->scopedBuildingIds()), 403);

        try {
            $this->turnovers->requestCleaning(
                $unit,
                $data['booking_id'] ? Booking::find($data['booking_id']) : null,
                auth()->user(),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Cleaning requested for unit {$unit->unit_number}.");
    }

    public function markCleaned(Request $request)
    {
        abort_unless(auth()->user()->can('request-cleaning'), 403);

        $data = $request->validate(['turnover_id' => 'required|exists:unit_turnovers,id']);

        $turnover = UnitTurnover::with('unit', 'building')->findOrFail($data['turnover_id']);
        abort_unless(in_array($turnover->building_id, $this->scopedBuildingIds()), 403);

        try {
            $this->turnovers->markCleaned($turnover, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Let QC know the unit is ready to inspect.
        NotificationService::send(
            NotificationService::getUsersByRoles(['quality-control', 'super-admin', 'ceo'], $turnover->building_id),
            new UnitReadyForQaNotification($turnover),
        );

        return back()->with('success', "Unit {$turnover->unit?->unit_number} marked cleaned. QC has been notified.");
    }

    public function cancelCleaning(Request $request)
    {
        abort_unless(auth()->user()->can('request-cleaning'), 403);

        $data = $request->validate(['turnover_id' => 'required|exists:unit_turnovers,id']);

        $turnover = UnitTurnover::with('unit')->findOrFail($data['turnover_id']);
        abort_unless(in_array($turnover->building_id, $this->scopedBuildingIds()), 403);

        $this->turnovers->cancelTurnover($turnover);

        return back()->with('success', "Cleaning for unit {$turnover->unit?->unit_number} cancelled.");
    }

    /** Combine a booking date with the building's standard time for display. */
    private function fmt($date, $time): array
    {
        return [
            'date' => Carbon::parse($date)->toISOString(),
            'time' => $time ? Carbon::parse($time)->format('g:i A') : null,
        ];
    }
}
