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
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Inertia\Inertia;

class HomeController extends Controller
{
    use ScopedByBuilding;

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
        if ($user->can('manage-availability')) {
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

        // ── Head of Procurement data ──────────────────────────
        if ($user->can('purchase-procurement')) {
            $data['pendingPurchases'] = ProcurementRequest::whereIn('building_id', $buildingIds)
                ->where('status', 'ceo_approved')
                ->with(['building', 'submittedBy'])
                ->latest()->limit(5)
                ->get(['id', 'reference', 'title', 'total_amount', 'building_id', 'submitted_by']);
        }

        return Inertia::render('Admin/Home', $data);
    }
}
