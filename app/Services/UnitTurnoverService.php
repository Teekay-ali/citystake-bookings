<?php

namespace App\Services;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitTurnover;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * The single place unit-turnover transitions happen. Each step guards the
 * allowed from-state and records its timestamp/actor, so the checkout → cleaned
 * → inspected → ready timeline is always consistent. Notifications and the
 * maintenance-request link are wired in later phases.
 */
class UnitTurnoverService
{
    /** Front desk requests cleaning for a just-vacated unit. */
    public function requestCleaning(Unit $unit, ?Booking $booking, User $by): UnitTurnover
    {
        if (UnitTurnover::where('unit_id', $unit->id)->active()->exists()) {
            throw new RuntimeException('This unit already has a turnover in progress.');
        }

        $buildingId = $unit->unitType?->building_id ?? $unit->building_id;

        return UnitTurnover::create([
            'unit_id'               => $unit->id,
            'building_id'           => $buildingId,
            'booking_id'            => $booking?->id,
            'status'                => 'cleaning_in_progress',
            'checked_out_at'        => $booking?->check_out ?? now(),
            'cleaning_requested_at' => now(),
            'cleaning_requested_by' => $by->id,
        ]);
    }

    /** Cleaning is done; the unit is ready for QA. */
    public function markCleaned(UnitTurnover $turnover, User $by): UnitTurnover
    {
        $this->assert($turnover, 'cleaning_in_progress');

        $turnover->update([
            'status'                => 'cleaning_completed',
            'cleaning_completed_at' => now(),
            'cleaning_completed_by' => $by->id,
        ]);

        return $turnover;
    }

    /** QC begins inspecting; links the QA inspection to the turnover. */
    public function startQa(UnitTurnover $turnover, UnitInspection $inspection): UnitTurnover
    {
        $this->assert($turnover, 'cleaning_completed');

        $turnover->update([
            'status'             => 'qa_in_progress',
            'qa_started_at'      => now(),
            'unit_inspection_id' => $inspection->id,
        ]);

        return $turnover;
    }

    /** Inspection passed — the unit is guest-ready. */
    public function completeQa(UnitTurnover $turnover): UnitTurnover
    {
        $this->assert($turnover, 'qa_in_progress');

        $turnover->update([
            'status'          => 'ready',
            'qa_completed_at' => now(),
            'ready_at'        => now(),
        ]);

        return $turnover;
    }

    /**
     * Pull a unit out of service: block the given dates and, if the unit has an
     * active turnover, mark it blocked. Reflects on Properties → Blocked Dates.
     */
    public function blockUnit(Unit $unit, string $from, string $to, string $reason, User $by): BlockedDate
    {
        $blocked = BlockedDate::create([
            'unit_id'      => $unit->id,
            'blocked_from' => $from,
            'blocked_to'   => $to,
            'reason'       => $reason,
            'notes'        => 'Flagged during QC inspection.',
            'created_by'   => $by->id,
        ]);

        $this->activeFor($unit)?->update([
            'status'          => 'blocked',
            'blocked_date_id' => $blocked->id,
        ]);

        return $blocked;
    }

    /**
     * Ensure a unit is in QA when QC starts inspecting it — whether it came
     * through cleaning (advance the turnover) or QC picked it directly (open a
     * fresh QA-only turnover so a pass still records as guest-ready).
     */
    public function beginQa(Unit $unit, UnitInspection $inspection): void
    {
        $active = $this->activeFor($unit);

        if ($active) {
            if ($active->status === 'cleaning_completed') {
                $this->startQa($active, $inspection);
            }
            return; // already in QA (resume), or mid-clean — nothing to open
        }

        UnitTurnover::create([
            'unit_id'            => $unit->id,
            'building_id'        => $unit->unitType?->building_id ?? $unit->building_id,
            'status'             => 'qa_in_progress',
            'qa_started_at'      => now(),
            'unit_inspection_id' => $inspection->id,
        ]);
    }

    /**
     * The canonical readiness state for a unit — the single source of truth used
     * by both the housekeeping board and the inspection round, so they never drift.
     * Returns: occupied | blocked | offline | needs_cleaning | cleaning |
     *          ready_for_qa | qa_in_progress | ready | pending
     */
    public function readinessState(bool $occupied, bool $blocked, ?UnitTurnover $turnover, $lastCheckout, bool $available): string
    {
        $active = $turnover && in_array($turnover->status, UnitTurnover::ACTIVE_STATUSES, true);

        // Checked out and not cleaned-and-passed since → needs cleaning.
        $needsCleaning = $lastCheckout
            && ! ($turnover && $turnover->ready_at && $turnover->ready_at->gte(Carbon::parse($lastCheckout)->endOfDay()));

        return match (true) {
            $blocked || ($turnover && $turnover->status === 'blocked') => 'blocked',
            $occupied                                                  => 'occupied',
            $active && $turnover->status === 'cleaning_in_progress'    => 'cleaning',
            $active && $turnover->status === 'cleaning_completed'      => 'ready_for_qa',
            $active && $turnover->status === 'qa_in_progress'          => 'qa_in_progress',
            $needsCleaning                                             => 'needs_cleaning',
            $turnover && $turnover->status === 'ready'                 => 'ready',
            ! $available                                              => 'offline',
            default                                                   => 'pending',
        };
    }

    /** The current in-flight turnover for a unit, if any. */
    public function activeFor(Unit $unit): ?UnitTurnover
    {
        return UnitTurnover::where('unit_id', $unit->id)->active()->latest('id')->first();
    }

    private function assert(UnitTurnover $turnover, string $expected): void
    {
        if ($turnover->status !== $expected) {
            throw new RuntimeException("This action isn't available for a turnover that is {$turnover->status}.");
        }
    }
}
