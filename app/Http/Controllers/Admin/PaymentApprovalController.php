<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\FinancialTransaction;
use App\Models\PaymentApproval;
use App\Models\User;
use App\Notifications\PaymentApprovalDecisionNotification;
use App\Notifications\PaymentApprovalRequestedNotification;
use App\Traits\ScopedByBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PaymentApprovalController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);

        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess()
            ? Building::pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $query = PaymentApproval::with(['requestedBy:id,name', 'approvedBy:id,name', 'building:id,name'])
            ->whereIn('building_id', $buildingIds);

        // Accountant only sees their own requests
        if ($user->hasRole('accountant')) {
            $query->where('requested_by', $user->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->building) {
            $query->where('building_id', $request->building);
        }

        $approvals = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'pending'  => PaymentApproval::whereIn('building_id', $buildingIds)
                ->when($user->hasRole('accountant'), fn($q) => $q->where('requested_by', $user->id))
                ->where('status', 'pending')->count(),
            'approved' => PaymentApproval::whereIn('building_id', $buildingIds)
                ->when($user->hasRole('accountant'), fn($q) => $q->where('requested_by', $user->id))
                ->where('status', 'approved')->count(),
            'paid'     => PaymentApproval::whereIn('building_id', $buildingIds)
                ->when($user->hasRole('accountant'), fn($q) => $q->where('requested_by', $user->id))
                ->where('status', 'paid')->count(),
        ];

        return Inertia::render('Admin/Finance/PaymentApprovals/Index', [
            'approvals' => $approvals,
            'summary'   => $summary,
            'buildings' => $this->accessibleBuildings()->get(['id', 'name']),
            'filters'   => $request->only(['status', 'building']),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);
        abort_unless(auth()->user()->hasRole(['accountant', 'super-admin']), 403);

        return Inertia::render('Admin/Finance/PaymentApprovals/Create', [
            'buildings' => $this->accessibleBuildings()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);
        abort_unless(auth()->user()->hasRole(['accountant', 'super-admin']), 403);

        $validated = $request->validate([
            'building_id'          => 'required|exists:buildings,id',
            'type'                 => 'required|in:salary,bonus,vendor_payment,utility,maintenance,miscellaneous',
            'custom_type'          => 'required_if:type,miscellaneous|nullable|string|max:100',
            'recipient_name'       => 'required|string|max:255',
            'amount'               => 'required|numeric|min:1',
            'description'          => 'nullable|string|max:2000',
            'bank_name'            => 'nullable|string|max:100',
            'account_number'       => 'nullable|string|max:20',
            'account_name'         => 'nullable|string|max:255',
        ]);

        $approval = PaymentApproval::create([
            ...$validated,
            'bank_name'           => $validated['bank_name'] ?? null,
            'account_number'      => $validated['account_number'] ?? null,
            'account_name'        => $validated['account_name'] ?? null,
            'requested_by'        => auth()->id(),
            'status'              => 'pending',
        ]);

        // Notify CEO and super-admin
        $recipients = User::role(['ceo', 'super-admin'])->get();
        foreach ($recipients as $recipient) {
            $recipient->notify(new PaymentApprovalRequestedNotification($approval));
        }

        AuditLog::log('payment_approval.requested', $approval, null, [
            'type'      => $approval->type_label,
            'amount'    => $approval->amount,
            'recipient' => $approval->recipient_name,
        ]);

        return redirect()->route('manage.payment-approvals.show', $approval->id)
            ->with('success', 'Payment approval request submitted.');
    }

    public function show(PaymentApproval $paymentApproval)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);

        // Accountant can only see their own
        if (auth()->user()->hasRole('accountant') &&
            $paymentApproval->requested_by !== auth()->id()) {
            abort(403);
        }

        $paymentApproval->load([
            'requestedBy:id,name',
            'approvedBy:id,name',
            'building:id,name',
            'documents.uploadedBy:id,name',
        ]);

        return Inertia::render('Admin/Finance/PaymentApprovals/Show', [
            'approval' => $paymentApproval,
            'canDecide' => auth()->user()->hasRole(['ceo', 'super-admin']) && $paymentApproval->isPending(),
            'canMarkPaid' => auth()->user()->hasRole(['accountant', 'super-admin']) &&
                $paymentApproval->isApproved() &&
                $paymentApproval->requested_by === auth()->id(),
            'canManageDocuments' => auth()->user()->hasRole(['accountant', 'super-admin']) &&
                $paymentApproval->requested_by === auth()->id() &&
                !$paymentApproval->isPaid(),
            'canDelete' => auth()->user()->can('manage-payment-approvals') &&
                $paymentApproval->isPending() &&
                ($paymentApproval->requested_by === auth()->id() || auth()->user()->hasRole('super-admin')),
        ]);
    }

    public function destroy(PaymentApproval $paymentApproval)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);
        abort_unless(
            $paymentApproval->requested_by === auth()->id() || auth()->user()->hasRole('super-admin'),
            403
        );
        abort_unless($paymentApproval->isPending(), 403, 'Only pending requests can be deleted.');

        // Delete associated documents
        foreach ($paymentApproval->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        // Delete payment evidence if any
        if ($paymentApproval->payment_evidence) {
            Storage::disk('public')->delete($paymentApproval->payment_evidence);
        }

        AuditLog::log('payment_approval.deleted', $paymentApproval, [
            'type'      => $paymentApproval->type_label,
            'amount'    => $paymentApproval->amount,
            'recipient' => $paymentApproval->recipient_name,
        ], null);

        $paymentApproval->delete();

        return redirect()->route('manage.payment-approvals.index')
            ->with('success', 'Payment request deleted.');
    }

    public function decide(Request $request, PaymentApproval $paymentApproval)
    {
        abort_unless(auth()->user()->hasRole(['ceo', 'super-admin']), 403);
        abort_unless($paymentApproval->isPending(), 403);

        $validated = $request->validate([
            'decision'    => 'required|in:approved,declined',
            'ceo_comment' => 'nullable|string|max:1000',
        ]);

        $paymentApproval->update([
            'status'      => $validated['decision'],
            'ceo_comment' => $validated['ceo_comment'] ?? null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Notify the accountant who requested it
        $requester = User::find($paymentApproval->requested_by);
        $requester?->notify(new PaymentApprovalDecisionNotification($paymentApproval));

        AuditLog::log("payment_approval.{$validated['decision']}", $paymentApproval,
            ['status' => 'pending'],
            ['status' => $validated['decision'], 'comment' => $validated['ceo_comment']]
        );

        return back()->with('success', 'Decision recorded.');
    }

    public function markPaid(Request $request, PaymentApproval $paymentApproval)
    {
        abort_unless(auth()->user()->can('manage-payment-approvals'), 403);
        abort_unless($paymentApproval->isApproved(), 403);
        abort_unless($paymentApproval->requested_by === auth()->id() || auth()->user()->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'payment_reference' => 'nullable|string|max:255',
            'payment_evidence'  => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $path = $request->file('payment_evidence')->store('payment-evidence', 'public');

        $paymentApproval->update([
            'status'             => 'paid',
            'payment_reference'  => $validated['payment_reference'] ?? null,
            'payment_evidence'   => $path,
            'paid_at'            => now(),
        ]);

        // Create financial transaction
        FinancialTransaction::create([
            'building_id'       => $paymentApproval->building_id,
            'recorded_by'       => auth()->id(),
            'type'              => 'expense',
            'category'          => 'manual_expense',
            'reference_type'    => PaymentApproval::class,
            'reference_id'      => $paymentApproval->id,
            'description'       => "{$paymentApproval->type_label} - {$paymentApproval->recipient_name}",
            'amount'            => $paymentApproval->amount,
            'payment_method'    => 'bank_transfer',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'transaction_date'  => now()->toDateString(),
        ]);

        AuditLog::log('payment_approval.paid', $paymentApproval,
            ['status' => 'approved'],
            ['status' => 'paid', 'reference' => $validated['payment_reference']]
        );

        return back()->with('success', 'Payment marked as completed.');
    }
}
