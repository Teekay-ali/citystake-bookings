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
        $buildingIds = $user->hasGlobalAccess()
            ? Building::pluck('id')->toArray()
            : ($user->accessibleBuildingIds() ?? []);

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
