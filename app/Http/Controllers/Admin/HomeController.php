<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Complaint;
use App\Models\FinancialTransaction;
use App\Models\MaintenanceReport;
use App\Models\ProcurementRequest;
use App\Models\Task;
use App\Models\Unit;
use App\Models\UnitTurnover;
use App\Models\BlockedDate;
use App\Services\UnitTurnoverService;
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    use ScopedByBuilding;

    private const OCCUPYING_STATUSES = ['confirmed', 'checked_in', 'paused'];

    public function __construct(private UnitTurnoverService $turnovers)
    {
    }

    public function index()
    {
        $user        = auth()->user();
        $today       = Carbon::today();
        $buildingIds = $this->scopedBuildingIds();

        $data = [
            'user'       => [
                'name'     => $user->name,
                'role'     => $user->getRoleNames()->first(),
                'building' => count($buildingIds) === 1
                    ? Building::find($buildingIds[0])?->name
                    : null,
            ],
        ];

        // ── My Tasks (all roles) ──────────────────────────────
        $data['myTasks'] = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['building', 'subtasks'])
            ->orderByRaw("
                CASE WHEN due_date IS NOT NULL AND due_date < CURDATE() THEN 0 ELSE 1 END,
                CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
                due_date ASC
            ")
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'priority'   => $t->priority,
                'status'     => $t->status,
                'due_date'   => $t->due_date?->toDateString(),
                'is_overdue' => $t->isOverdue(),
                'building'   => $t->building?->name,
                'progress'   => $t->completionPercent(),
            ]);

        // ── Receptionist data ─────────────────────────────────
        // Front-desk cards (check-ins/outs, occupancy) are for booking staff.
        // QC may hold manage-availability (to reach Housekeeping) but shouldn't
        // get the reception dashboard, so also require booking duties.
        if ($user->can('manage-availability') && $user->can('manage-bookings')) {
            $data['todayCheckins'] = Booking::whereIn('building_id', $buildingIds)
                ->whereDate('check_in', $today)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->with(['unit', 'unitType', 'building'])
                ->get(['id', 'booking_reference', 'guest_name', 'guest_phone',
                    'building_id', 'unit_id', 'unit_type_id', 'status', 'check_in', 'check_out']);

            $data['currentlyOccupied'] = Booking::whereIn('building_id', $buildingIds)
                ->where('status', 'checked_in')
                ->with(['unit', 'unitType', 'building'])
                ->get(['id', 'booking_reference', 'guest_name', 'guest_phone',
                    'building_id', 'unit_id', 'unit_type_id', 'status', 'check_in', 'check_out', 'checked_in_at'])
                ->map(fn($b) => [
                    'id'               => $b->id,
                    'booking_reference'=> $b->booking_reference,
                    'guest_name'       => $b->guest_name,
                    'guest_phone'      => $b->guest_phone,
                    'unit_number'      => $b->unit?->unit_number,
                    'unit_type'        => $b->unitType?->name,
                    'building'         => $b->building?->name,
                    'check_in'         => $b->check_in?->toDateString(),
                    'check_out'        => $b->check_out?->toDateString(),
                    'checked_in_at'    => $b->checked_in_at?->toDateTimeString(),
                ]);

            $data['todayCheckouts'] = Booking::whereIn('building_id', $buildingIds)
                ->whereDate('check_out', $today)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->with(['unit', 'unitType', 'building'])
                ->get(['id', 'booking_reference', 'guest_name',
                    'building_id', 'unit_id', 'unit_type_id', 'status', 'check_in', 'check_out']);

            $data['availability'] = [
                'total'     => \App\Models\Unit::whereHas('unitType', fn($q) =>
                $q->whereIn('building_id', $buildingIds))
                    ->where('is_available', true)->count(),
                'occupied'  => Booking::whereIn('building_id', $buildingIds)
                    ->whereDate('check_in', '<=', $today)
                    ->whereDate('check_out', '>', $today)
                    ->whereNotIn('status', ['cancelled'])->count(),
            ];
        }

        // ── Manager data ──────────────────────────────────────
        if ($user->can('manage-complaints')) {
            $data['openComplaints'] = Complaint::whereIn('building_id', $buildingIds)
                ->where('status', 'open')->count();

            $data['pendingMaintenance'] = MaintenanceReport::whereIn('building_id', $buildingIds)
                ->where('status', 'pending')->count();

            $data['openTasks'] = Task::whereIn('building_id', $buildingIds)
                ->whereNotIn('status', ['completed', 'cancelled'])->count();

            $data['recentComplaints'] = Complaint::whereIn('building_id', $buildingIds)
                ->where('status', 'open')
                ->with(['building', 'submittedBy'])
                ->latest()->limit(3)
                ->get(['id', 'title', 'severity', 'building_id', 'submitted_by', 'created_at']);

            // ── Operational charts (no financial data) ──
            $totalUnits = \App\Models\Unit::whereHas('unitType', fn ($q) => $q->whereIn('building_id', $buildingIds))
                ->where('is_available', true)->count();

            // Occupancy % snapshot at the start of each of the last 12 weeks.
            $occupancyTrend = [];
            for ($i = 11; $i >= 0; $i--) {
                $weekStart = $today->copy()->startOfWeek()->subWeeks($i);
                $occupied = Booking::whereIn('building_id', $buildingIds)
                    ->whereNotIn('status', ['cancelled'])
                    ->whereDate('check_in', '<=', $weekStart)
                    ->whereDate('check_out', '>', $weekStart)
                    ->distinct('unit_id')->count('unit_id');
                $occupancyTrend[] = [
                    'label' => $weekStart->format('d M'),
                    'rate'  => $totalUnits > 0 ? (int) round($occupied / $totalUnits * 100) : 0,
                ];
            }

            // New bookings per month (count only), last 6 months.
            $volumeRaw = Booking::whereIn('building_id', $buildingIds)
                ->where('created_at', '>=', $today->copy()->subMonths(5)->startOfMonth())
                ->selectRaw('YEAR(created_at) yr, MONTH(created_at) mo, COUNT(*) c')
                ->groupBy('yr', 'mo')->get()
                ->keyBy(fn ($r) => sprintf('%d-%02d', $r->yr, $r->mo));
            $bookingVolume = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = $today->copy()->subMonths($i);
                $bookingVolume[] = [
                    'month' => $m->format('M'),
                    'count' => (int) ($volumeRaw[$m->format('Y-m')]->c ?? 0),
                ];
            }

            // Current booking status mix.
            $statusRaw = Booking::whereIn('building_id', $buildingIds)
                ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
            $statusMix = [
                ['label' => 'Confirmed',  'value' => (int) ($statusRaw['confirmed'] ?? 0)],
                ['label' => 'Checked in', 'value' => (int) ($statusRaw['checked_in'] ?? 0)],
                ['label' => 'Completed',  'value' => (int) ($statusRaw['completed'] ?? 0)],
                ['label' => 'Cancelled',  'value' => (int) ($statusRaw['cancelled'] ?? 0)],
            ];

            $data['charts'] = [
                'occupancyTrend' => $occupancyTrend,
                'bookingVolume'  => $bookingVolume,
                'statusMix'      => $statusMix,
            ];
        }

        // ── Accountant data ───────────────────────────────────
        if ($user->can('manage-financials')) {
            $data['pendingPayments'] = [
                'maintenance' => MaintenanceReport::whereIn('building_id', $buildingIds)
                    ->where('status', 'ceo_approved')->count(),
                'procurement' => ProcurementRequest::whereIn('building_id', $buildingIds)
                    ->where('status', 'ceo_approved')->count(),
            ];

            $data['monthRevenue'] = (float) FinancialTransaction::whereIn('building_id', $buildingIds)
                ->where('type', 'income')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount');

            $data['monthExpenses'] = (float) FinancialTransaction::whereIn('building_id', $buildingIds)
                ->where('type', 'expense')
                ->whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)
                ->sum('amount');
        }

        // ── Procurement Officer dashboard ─────────────────────
        if ($user->can('approve-procurement-officer')) {
            $counts = ProcurementRequest::whereIn('building_id', $buildingIds)
                ->selectRaw('status, COUNT(*) c, SUM(total_amount) s')
                ->groupBy('status')->get()->keyBy('status');

            $listCols = ['id', 'reference', 'title', 'total_amount', 'building_id', 'submitted_by', 'created_at'];

            $data['procurement'] = [
                // The two points where the officer personally acts
                'to_review'   => (int) ($counts['pending']->c ?? 0),
                'to_purchase' => (int) ($counts['ceo_approved']->c ?? 0),
                // Sitting with accountant/CEO - awareness, not action
                'in_approval' => (int) (($counts['officer_approved']->c ?? 0) + ($counts['accountant_approved']->c ?? 0)),
                // Value moving through the pipeline right now (non-terminal, non-rejected)
                'open_value'  => (float) (
                    ($counts['pending']->s ?? 0) + ($counts['officer_approved']->s ?? 0)
                    + ($counts['accountant_approved']->s ?? 0) + ($counts['ceo_approved']->s ?? 0)
                ),
                'completed_month' => ProcurementRequest::whereIn('building_id', $buildingIds)
                    ->where('status', 'completed')
                    ->whereMonth('updated_at', $today->month)->whereYear('updated_at', $today->year)
                    ->count(),

                'reviewQueue' => ProcurementRequest::whereIn('building_id', $buildingIds)
                    ->where('status', 'pending')
                    ->with(['building', 'submittedBy'])
                    ->latest()->limit(6)->get($listCols),

                'purchaseQueue' => ProcurementRequest::whereIn('building_id', $buildingIds)
                    ->where('status', 'ceo_approved')
                    ->with(['building', 'submittedBy'])
                    ->latest()->limit(6)->get($listCols),
            ];
        }

        // ── Quality Control dashboard ─────────────────────────
        // A live overview of where every unit sits in the turnover lifecycle,
        // plus the QC worklist (units cleaned and waiting to be inspected).
        if ($user->can('conduct-inspections')) {
            $data['qc'] = $this->qualityControl($buildingIds, $today);
        }

        return Inertia::render('Admin/Home', $data);
    }

    /**
     * Turnover-lifecycle overview for QC: a state breakdown across all scoped
     * units and the queue of units ready for inspection. Uses the shared
     * readinessState resolver so it never drifts from the housekeeping board.
     */
    private function qualityControl(array $buildingIds, Carbon $today): array
    {
        $units = Unit::whereHas('unitType', fn ($q) => $q->whereIn('building_id', $buildingIds))
            ->with('unitType:id,name,building_id')
            ->orderBy('unit_number')
            ->get();
        $unitIds = $units->pluck('id');

        $occupied = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', self::OCCUPYING_STATUSES)
            ->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)
            ->pluck('unit_id')->unique();

        $lastCheckout = Booking::whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled'])
            ->where(fn ($q) => $q->whereNotNull('checked_out_at')->orWhereDate('check_out', '<=', $today))
            ->get(['id', 'unit_id', 'check_out', 'checked_out_at'])
            ->groupBy('unit_id')
            ->map(fn ($g) => $g->sortByDesc(fn ($b) => $b->checked_out_at ?? $b->check_out)->first());

        $nextArrival = Booking::whereIn('unit_id', $unitIds)
            ->whereIn('status', ['confirmed', 'pending'])->whereDate('check_in', '>=', $today)
            ->orderBy('check_in')->get(['id', 'unit_id', 'guest_name', 'check_in'])
            ->groupBy('unit_id')->map->first();

        $turnovers = UnitTurnover::whereIn('unit_id', $unitIds)->get()
            ->groupBy('unit_id')->map(fn ($g) => $g->sortByDesc('id')->first());

        $blocked = BlockedDate::whereIn('unit_id', $unitIds)
            ->whereDate('blocked_from', '<=', $today)->whereDate('blocked_to', '>=', $today)
            ->pluck('unit_id')->unique();

        $buildings = Building::whereIn('id', $buildingIds)->pluck('name', 'id');

        $rows = $units->map(function ($u) use ($occupied, $lastCheckout, $nextArrival, $turnovers, $blocked, $buildings) {
            $to  = $turnovers->get($u->id);
            $out = $lastCheckout->get($u->id);
            $arr = $nextArrival->get($u->id);
            $active = $to && in_array($to->status, UnitTurnover::ACTIVE_STATUSES, true);
            $departedAt = $out ? ($out->checked_out_at ?? $out->check_out) : null;

            $state = $this->turnovers->readinessState(
                $occupied->contains($u->id), $blocked->contains($u->id), $to, $departedAt, $u->status === 'available'
            );

            return [
                'unit_id'     => $u->id,
                'unit_number' => $u->unit_number,
                'unit_type'   => $u->unitType->name,
                'building'    => $buildings[$u->unitType->building_id] ?? null,
                'state'       => $state,
                'turnover_id' => $active ? $to->id : null,
                'since'       => match ($state) {
                    'ready_for_qa'   => $to?->cleaning_completed_at?->toISOString(),
                    'qa_in_progress' => $to?->qa_started_at?->toISOString(),
                    default          => null,
                },
                'arrival'     => $arr?->check_in?->toISOString(),
            ];
        });

        $by = $rows->countBy('state');

        // The QC worklist: units cleaned and awaiting inspection, then those mid-QA.
        // Soonest next arrival first so the most time-critical turnovers surface.
        $queue = $rows->whereIn('state', ['ready_for_qa', 'qa_in_progress'])
            ->sortBy(fn ($u) => ($u['state'] === 'qa_in_progress' ? '0' : '1') . '|' . ($u['arrival'] ?? '9999'))
            ->take(8)->values();

        return [
            'counts' => [
                'ready_for_qa'   => (int) ($by['ready_for_qa'] ?? 0),
                'qa_in_progress' => (int) ($by['qa_in_progress'] ?? 0),
                'needs_cleaning' => (int) ($by['needs_cleaning'] ?? 0),
                'cleaning'       => (int) ($by['cleaning'] ?? 0),
                'ready'          => (int) ($by['ready'] ?? 0),
                'blocked'        => (int) ($by['blocked'] ?? 0),
                'occupied'       => (int) ($by['occupied'] ?? 0),
                'total'          => $rows->count(),
            ],
            'queue' => $queue,
        ];
    }
}
