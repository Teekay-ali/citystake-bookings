<?php

namespace App\Http\Controllers\Admin;

use App\Services\NotificationService;
use App\Notifications\MaintenanceStatusNotification;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\MaintenanceReport;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MaintenanceReport::with(['building', 'submittedBy', 'vendor'])
            ->latest();

        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        if ($request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->issue_type) {
            $query->where('issue_type', $request->issue_type);
        }

        $reports = $query->paginate(20)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        return Inertia::render('Admin/Maintenance/Index', [
            'reports'    => $reports,
            'buildings'  => $buildings,
            'issueTypes' => MaintenanceReport::issueTypes(),
            'filters'    => $request->only(['building_id', 'status', 'issue_type']),
            'counts'     => [
                'pending'             => MaintenanceReport::scopedToUser($user)->where('status', 'pending')->count(),
                'manager_approved'    => MaintenanceReport::scopedToUser($user)->where('status', 'manager_approved')->count(),
                'accountant_approved' => MaintenanceReport::scopedToUser($user)->where('status', 'accountant_approved')->count(),
                'ceo_approved'        => MaintenanceReport::scopedToUser($user)->where('status', 'ceo_approved')->count(),
            ],
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->where('is_active', true)->get(['id', 'name']);

        $vendors = Vendor::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'phone']);

        return Inertia::render('Admin/Maintenance/Create', [
            'buildings'  => $buildings,
            'vendors'    => $vendors,
            'issueTypes' => MaintenanceReport::issueTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id'     => 'required|exists:buildings,id',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'title'           => 'required|string|max:255',
            'issue_type'      => 'required|in:' . implode(',', array_keys(MaintenanceReport::issueTypes())),
            'description'     => 'required|string|max:2000',
            'location'        => 'nullable|string|max:255',
            'estimated_cost'  => 'nullable|numeric|min:0',
            'repair_timeline' => 'nullable|date',
            'notes'           => 'nullable|string|max:1000',
            'photos'          => 'nullable|array|max:5',
            'photos.*'        => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('maintenance', 'public');
            }
        }

        MaintenanceReport::create([
            ...$validated,
            'submitted_by' => auth()->id(),
            'photos'       => $photoPaths ?: null,
        ]);

        return redirect()->route('manage.maintenance.index')
            ->with('success', 'Maintenance report submitted successfully.');
    }

    public function show(MaintenanceReport $maintenance)
    {
        $this->authorizeBuilding($maintenance);

        $maintenance->load([
            'building', 'vendor', 'submittedBy',
            'managerApprovedBy', 'accountantApprovedBy',
            'ceoApprovedBy', 'paymentMadeBy',
        ]);

        return Inertia::render('Admin/Maintenance/Show', [
            'report' => array_merge($maintenance->toArray(), [
                'photo_urls'   => collect($maintenance->photos ?? [])
                    ->map(fn($p) => Storage::url($p))->values(),
                'status_label' => $maintenance->statusLabel(),
                'can_manager_approve'    => $maintenance->canManagerApprove(),
                'can_accountant_approve' => $maintenance->canAccountantApprove(),
                'can_ceo_approve'        => $maintenance->canCeoApprove(),
                'can_make_payment'       => $maintenance->canMakePayment(),
            ]),
            'issueTypes' => MaintenanceReport::issueTypes(),
        ]);
    }

    public function approve(Request $request, MaintenanceReport $maintenance)
    {
        $this->authorizeBuilding($maintenance);
        $user = auth()->user();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes'  => 'nullable|string|max:500',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validated['action'] === 'reject') {
            $maintenance->update([
                'status'           => 'rejected',
                'rejection_reason' => $validated['notes'],
                'rejected_by_role' => $user->getRoleNames()->first(),
            ]);
            return back()->with('success', 'Report rejected.');
        }

        // Approve — advance the workflow
        if ($maintenance->canManagerApprove() && $user->can('approve-maintenance-manager')) {
            $maintenance->update([
                'status'              => 'manager_approved',
                'manager_approved_by' => $user->id,
                'manager_approved_at' => now(),
            ]);

            $recipients = NotificationService::getUsersByRoles(['accountant'], $report->building_id);
            Notification::send($recipients, new MaintenanceStatusNotification(
                $report,
                'Maintenance Awaiting Accountant Approval',
                "Maintenance report \"{$report->title}\" approved by manager - needs your cost approval."
            ));

        } elseif ($maintenance->canAccountantApprove() && $user->can('approve-maintenance-accountant')) {
            $validated2 = $request->validate(['actual_cost' => 'required|numeric|min:0']);
            $maintenance->update([
                'status'                 => 'accountant_approved',
                'accountant_approved_by' => $user->id,
                'accountant_approved_at' => now(),
                'actual_cost'            => $validated2['actual_cost'],
            ]);

            $recipients = NotificationService::getUsersByRoles(['ceo'], $report->building_id);
            Notification::send($recipients, new MaintenanceStatusNotification(
                $report,
                'Maintenance Awaiting CEO Approval',
                "Maintenance report \"{$report->title}\" needs your final approval."
            ));

        } elseif ($maintenance->canCeoApprove() && $user->can('approve-maintenance-ceo')) {
            $maintenance->update([
                'status'          => 'ceo_approved',
                'ceo_approved_by' => $user->id,
                'ceo_approved_at' => now(),
            ]);

            $recipients = NotificationService::getUsersByRoles(['accountant'], $report->building_id);
            Notification::send($recipients, new MaintenanceStatusNotification(
                $report,
                'Maintenance Ready for Payment',
                "CEO approved \"{$report->title}\". Please process payment."
            ));

        } elseif ($maintenance->canMakePayment() && $user->can('pay-maintenance')) {
            $maintenance->update([
                'status'          => 'completed',
                'payment_made_by' => $user->id,
                'payment_made_at' => now(),
            ]);
        } else {
            return back()->with('error', 'You are not authorized to perform this action.');
        }

        return back()->with('success', 'Report updated successfully.');
    }

    public function destroy(MaintenanceReport $maintenance)
    {
        $this->authorizeBuilding($maintenance);

        foreach ($maintenance->photos ?? [] as $path) {
            Storage::disk('public')->delete($path);
        }

        $maintenance->delete();

        return redirect()->route('manage.maintenance.index')
            ->with('success', 'Report deleted.');
    }

    private function authorizeBuilding(MaintenanceReport $maintenance): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($maintenance->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}
