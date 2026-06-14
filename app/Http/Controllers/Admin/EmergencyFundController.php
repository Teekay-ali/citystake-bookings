<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\EmergencyFundRequest;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Notifications\EmergencyFundDecisionNotification;
use App\Notifications\EmergencyFundRequestedNotification;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmergencyFundController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request): Response
    {
        abort_unless(auth()->user()->can('manage-emergency-fund'), 403);

        $user        = auth()->user();
        $buildings   = $this->accessibleBuildings()->get(['id', 'name', 'monthly_emergency_limit']);
        $buildingIds = $buildings->pluck('id')->toArray();
        $currentMonth = now()->format('Y-m');

        // Per-building fund summary for current month
        $fundSummary = $buildings->map(function ($building) use ($currentMonth) {
            $used      = EmergencyFundRequest::usedThisMonth($building->id, $currentMonth);
            $limit     = (float) $building->monthly_emergency_limit;
            $remaining = max(0, $limit - $used);
            $percent   = $limit > 0 ? min(100, round(($used / $limit) * 100)) : 0;

            return [
                'building_id'   => $building->id,
                'building_name' => $building->name,
                'limit'         => $limit,
                'used'          => $used,
                'remaining'     => $remaining,
                'percent'       => $percent,
            ];
        });

        // Requests list
        $query = EmergencyFundRequest::with(['requestedBy:id,name', 'approvedBy:id,name', 'building:id,name'])
            ->whereIn('building_id', $buildingIds)
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->building, fn($q) => $q->where('building_id', $request->building))
            ->when($request->month,    fn($q) => $q->where('month_year', $request->month))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Month options for filter (last 6 months)
        $months = collect(range(0, 5))->map(fn($i) => [
            'value' => now()->subMonths($i)->format('Y-m'),
            'label' => now()->subMonths($i)->format('F Y'),
        ]);

        return Inertia::render('Admin/Finance/EmergencyFund/Index', [
            'requests'    => $query,
            'fundSummary' => $fundSummary,
            'buildings'   => $buildings,
            'months'      => $months,
            'filters'     => $request->only(['status', 'building', 'month']),
            'currentMonth' => $currentMonth,
        ]);
    }

    public function create(): Response
    {
        abort_unless(auth()->user()->can('manage-emergency-fund'), 403);
        abort_unless(auth()->user()->hasRole(['accountant', 'super-admin']), 403);

        $buildings    = $this->accessibleBuildings()->get(['id', 'name', 'monthly_emergency_limit']);
        $currentMonth = now()->format('Y-m');

        $buildingFunds = $buildings->map(fn($b) => [
            'id'        => $b->id,
            'name'      => $b->name,
            'limit'     => (float) $b->monthly_emergency_limit,
            'used'      => EmergencyFundRequest::usedThisMonth($b->id, $currentMonth),
            'remaining' => EmergencyFundRequest::remainingThisMonth($b->id, (float) $b->monthly_emergency_limit, $currentMonth),
        ]);

        return Inertia::render('Admin/Finance/EmergencyFund/Create', [
            'buildingFunds' => $buildingFunds,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-emergency-fund'), 403);
        abort_unless(auth()->user()->hasRole(['accountant', 'super-admin']), 403);

        $validated = $request->validate([
            'building_id'         => 'required|exists:buildings,id',
            'amount'              => 'required|numeric|min:1',
            'reason'              => 'required|string|max:255',
            'urgency_description' => 'required|string|max:2000',
            'evidence'            => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        // Check remaining balance
        $building  = Building::findOrFail($validated['building_id']);
        $remaining = EmergencyFundRequest::remainingThisMonth(
            $building->id,
            (float) $building->monthly_emergency_limit
        );

        if ($validated['amount'] > $remaining) {
            return back()->withErrors([
                'amount' => "Amount exceeds remaining emergency fund balance of ₦" . number_format($remaining, 0) . " for {$building->name} this month.",
            ])->withInput();
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('emergency-fund/evidence', 'public');
        }

        $efRequest = EmergencyFundRequest::create([
            ...$validated,
            'evidence'     => $evidencePath,
            'requested_by' => auth()->id(),
            'status'       => 'pending',
            'month_year'   => now()->format('Y-m'),
        ]);

        $efRequest->load('requestedBy', 'building');

        // Notify CEO and super-admin
        User::role(['ceo', 'super-admin'])->each(
            fn($u) => $u->notify(new EmergencyFundRequestedNotification($efRequest))
        );

        AuditLog::log('emergency_fund.requested', $efRequest, null, [
            'amount'   => $efRequest->amount,
            'building' => $efRequest->building->name,
            'reason'   => $efRequest->reason,
        ]);

        return redirect()->route('manage.emergency-fund.show', $efRequest->id)
            ->with('success', 'Emergency fund request submitted successfully.');
    }

    public function show(EmergencyFundRequest $emergencyFund): Response
    {
        $user = auth()->user();
        abort_unless($user->can('manage-emergency-fund'), 403);
        if (! $user->hasGlobalAccess()) {
            abort_unless(in_array($emergencyFund->building_id, $user->accessibleBuildingIds() ?? []), 403);
        }

        $emergencyFund->load(['requestedBy:id,name', 'approvedBy:id,name', 'building:id,name,monthly_emergency_limit']);

        $currentMonth = now()->format('Y-m');
        $limit        = (float) $emergencyFund->building->monthly_emergency_limit;
        $used         = EmergencyFundRequest::usedThisMonth($emergencyFund->building_id, $currentMonth);
        $remaining    = max(0, $limit - $used);

        return Inertia::render('Admin/Finance/EmergencyFund/Show', [
            'efRequest'   => $emergencyFund,
            'fundBalance' => [
                'limit'     => $limit,
                'used'      => $used,
                'remaining' => $remaining,
                'percent'   => $limit > 0 ? min(100, round(($used / $limit) * 100)) : 0,
            ],
            'canManagerDecide' => auth()->user()->hasRole(['manager', 'super-admin']) && $emergencyFund->isPending(),
            'canCeoDecide'     => auth()->user()->hasRole(['ceo', 'super-admin']) && $emergencyFund->isManagerApproved(),
            'canMarkPaid'      => auth()->user()->hasRole(['accountant', 'super-admin']) &&
                $emergencyFund->isApproved() &&
                (int) $emergencyFund->requested_by === auth()->id(),
            'canDelete'        => $emergencyFund->isPending() &&
                ((int) $emergencyFund->requested_by === auth()->id() || auth()->user()->hasRole('super-admin')),
        ]);
    }

    public function managerDecide(Request $request, EmergencyFundRequest $emergencyFund)
    {
        abort_unless(auth()->user()->hasRole(['manager', 'super-admin']), 403);
        abort_unless($emergencyFund->isPending(), 403);

        $validated = $request->validate([
            'decision'        => 'required|in:manager_approved,declined',
            'manager_comment' => 'nullable|string|max:1000',
        ]);

        $emergencyFund->update([
            'status'               => $validated['decision'],
            'manager_comment'      => $validated['manager_comment'] ?? null,
            'manager_approved_by'  => auth()->id(),
            'manager_approved_at'  => now(),
        ]);

        $emergencyFund->load('managerApprovedBy', 'requestedBy', 'building');

        if ($validated['decision'] === 'manager_approved') {
            // Notify CEO for final approval
            User::role(['ceo', 'super-admin'])->each(
                fn($u) => $u->notify(new EmergencyFundRequestedNotification($emergencyFund))
            );
        } else {
            // Notify accountant of decline
            $requester = User::find($emergencyFund->requested_by);
            $requester?->notify(new EmergencyFundDecisionNotification($emergencyFund));
        }

        AuditLog::log("emergency_fund.{$validated['decision']}", $emergencyFund,
            ['status' => 'pending'],
            ['status' => $validated['decision'], 'comment' => $validated['manager_comment']]
        );

        return back()->with('success', 'Decision recorded.');
    }

    public function decide(Request $request, EmergencyFundRequest $emergencyFund)
    {
        abort_unless(auth()->user()->hasRole(['ceo', 'super-admin']), 403);
        abort_unless($emergencyFund->isManagerApproved(), 403);

        $validated = $request->validate([
            'decision'    => 'required|in:approved,declined',
            'ceo_comment' => 'nullable|string|max:1000',
        ]);

        $emergencyFund->update([
            'status'      => $validated['decision'],
            'ceo_comment' => $validated['ceo_comment'] ?? null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $emergencyFund->load('approvedBy', 'requestedBy', 'building');

        $requester = User::find($emergencyFund->requested_by);
        $requester?->notify(new EmergencyFundDecisionNotification($emergencyFund));

        AuditLog::log("emergency_fund.{$validated['decision']}", $emergencyFund,
            ['status' => 'pending'],
            ['status' => $validated['decision'], 'comment' => $validated['ceo_comment']]
        );

        return back()->with('success', 'Decision recorded successfully.');
    }

    public function markPaid(Request $request, EmergencyFundRequest $emergencyFund)
    {
        abort_unless(auth()->user()->can('manage-emergency-fund'), 403);
        abort_unless($emergencyFund->isApproved(), 403);
        abort_unless(
            (int) $emergencyFund->requested_by === auth()->id() || auth()->user()->hasRole('super-admin'),
            403
        );

        $validated = $request->validate([
            'payment_reference' => 'nullable|string|max:255',
            'payment_evidence'  => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $path = $request->file('payment_evidence')->store('emergency-fund/payments', 'public');

        $emergencyFund->update([
            'status'            => 'paid',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'evidence'          => $path,
            'paid_at'           => now(),
        ]);

        // Create financial transaction
        FinancialTransaction::create([
            'building_id'       => $emergencyFund->building_id,
            'recorded_by'       => auth()->id(),
            'type'              => 'expense',
            'category'          => 'manual_expense',
            'reference_type'    => EmergencyFundRequest::class,
            'reference_id'      => $emergencyFund->id,
            'description'       => "Emergency Fund - {$emergencyFund->reason}",
            'amount'            => $emergencyFund->amount,
            'payment_method'    => 'cash',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'transaction_date'  => now()->toDateString(),
        ]);

        AuditLog::log('emergency_fund.paid', $emergencyFund,
            ['status' => 'approved'],
            ['status' => 'paid', 'reference' => $validated['payment_reference']]
        );

        return back()->with('success', 'Emergency fund payment recorded.');
    }

    public function destroy(EmergencyFundRequest $emergencyFund)
    {
        abort_unless(auth()->user()->can('manage-emergency-fund'), 403);
        abort_unless(
            ((int) $emergencyFund->requested_by === auth()->id() || auth()->user()->hasRole('super-admin')) &&
            $emergencyFund->isPending(),
            403
        );

        if ($emergencyFund->evidence) {
            Storage::disk('public')->delete($emergencyFund->evidence);
        }

        AuditLog::log('emergency_fund.deleted', $emergencyFund, [
            'amount'   => $emergencyFund->amount,
            'building' => $emergencyFund->building->name,
        ], null);

        $emergencyFund->delete();

        return redirect()->route('manage.emergency-fund.index')
            ->with('success', 'Request deleted.');
    }
}
