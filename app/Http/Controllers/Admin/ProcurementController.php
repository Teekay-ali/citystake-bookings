<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use App\Models\StockItem;
use App\Models\StockLog;
use App\Models\User;
use App\Notifications\ProcurementStatusNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\ProcurementRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-procurement'), 403);

        $user = auth()->user();

        $query = ProcurementRequest::with(['building', 'submittedBy', 'vendor', 'items'])
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

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }

        $requests = $query->paginate(10)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        return Inertia::render('Admin/Procurement/Index', [
            'requests'  => $requests,
            'buildings' => $buildings,
            'filters' => $request->only(['building_id', 'status', 'search']),
            'counts' => ProcurementRequest::scopedToUser($user)
                ->whereIn('status', ['pending', 'accountant_approved', 'ceo_approved', 'purchased'])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->only(['pending', 'accountant_approved', 'ceo_approved', 'purchased'])
                ->toArray(),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->can('submit-procurement'), 403);

        $user = auth()->user();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->where('is_active', true)->get(['id', 'name']);

        $vendors = Vendor::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'bank_name', 'bank_account_number', 'bank_account_name']);

        // Completed requests this one can be a continuation of (searchable picker).
        $completedRequests = ProcurementRequest::where('status', 'completed')
            ->when(! $user->hasGlobalAccess(), fn ($q) => $q->whereIn('building_id', $user->accessibleBuildingIds() ?? []))
            ->latest()
            ->limit(300)
            ->get(['id', 'reference', 'title']);

        return Inertia::render('Admin/Procurement/Create', [
            'buildings'         => $buildings,
            'vendors'           => $vendors,
            'completedRequests' => $completedRequests,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id'    => 'required|exists:buildings,id',
            'title'          => 'required|string|max:255',
            'justification'  => 'nullable|string|max:1000',
            'notes'          => 'nullable|string|max:1000',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'supplier_name'  => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_bank_name'       => 'nullable|string|max:100',
            'supplier_account_number'  => 'nullable|string|max:20',
            'supplier_account_name'    => 'nullable|string|max:255',
            'related_request_id' => 'nullable|exists:procurement_requests,id',
            'items'          => 'required|array|min:1',
            'items.*.name'        => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.track_stock' => 'boolean',
        ]);

        // A continuation may only link to a completed request in an accessible building.
        if (! empty($validated['related_request_id'])) {
            $related = ProcurementRequest::find($validated['related_request_id']);
            $user    = auth()->user();
            if (! $related || $related->status !== 'completed'
                || (! $user->hasGlobalAccess() && ! in_array($related->building_id, $user->accessibleBuildingIds() ?? []))) {
                return back()->with('error', 'You can only continue from a completed request you have access to.')->withInput();
            }
        }

        $pr = ProcurementRequest::create([
            'reference'      => ProcurementRequest::generateReference(),
            'building_id'    => $validated['building_id'],
            'related_request_id' => $validated['related_request_id'] ?? null,
            'submitted_by'   => auth()->id(),
            'title'          => $validated['title'],
            'justification'  => $validated['justification'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'vendor_id'      => $validated['vendor_id'] ?? null,
            'supplier_name'  => $validated['supplier_name'] ?? null,
            'supplier_phone' => $validated['supplier_phone'] ?? null,
            'supplier_email' => $validated['supplier_email'] ?? null,
            'supplier_bank_name'       => $validated['supplier_bank_name'] ?? null,
            'supplier_account_number'  => $validated['supplier_account_number'] ?? null,
            'supplier_account_name'    => $validated['supplier_account_name'] ?? null,
        ]);

        $total = 0;
        foreach ($validated['items'] as $item) {
            // A null unit_price means "pricing pending" - the officer fills it in.
            $unitPrice = $item['unit_price'] ?? null;
            $lineTotal = $unitPrice === null ? null : $item['quantity'] * $unitPrice;
            $total += $lineTotal ?? 0;
            $pr->items()->create([
                'name'        => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity'    => $item['quantity'],
                'unit_price'  => $unitPrice,
                'total_price' => $lineTotal,
                'track_stock' => $item['track_stock'] ?? true,
            ]);
        }

        $pr->update(['total_amount' => $total]);

        AuditLog::log('procurement.submitted', $pr, null, ['reference' => $pr->reference, 'title' => $pr->title, 'total' => $pr->total_amount]);

        // The Procurement Officer is also the first reviewer, so a request they
        // raise skips their own review and goes straight to the accountant -
        // provided every item is priced (the officer stage's pricing rule).
        $isOfficer   = auth()->user()->can('approve-procurement-officer');
        $hasUnpriced = $pr->items()->whereNull('unit_price')->exists();

        if ($isOfficer && ! $hasUnpriced) {
            $pr->update([
                'status'              => 'officer_approved',
                'officer_approved_by' => auth()->id(),
                'officer_approved_at' => now(),
            ]);
            AuditLog::log('procurement.status_updated', $pr, ['status' => 'pending'], ['status' => 'officer_approved']);

            $this->notifyProcurement(
                $pr,
                'Procurement Awaiting Accountant Approval',
                "Procurement request \"{$pr->title}\" was raised by the Procurement Officer and needs the accountant's approval.",
                ['accountant'],
            );

            return redirect()->route('manage.procurement.index')
                ->with('success', 'Request submitted and sent to the accountant for approval.');
        }

        $this->notifyProcurement(
            $pr,
            'New Procurement Request',
            "\"{$pr->title}\" has been submitted and needs the Procurement Officer's review.",
            ['head-of-procurement'],
        );

        return redirect()->route('manage.procurement.index')
            ->with('success', 'Procurement request submitted successfully.');
    }

    /**
     * Procurement Officer edits a request during their review — correct items,
     * fill in prices, fix supplier details. Only allowed before the officer has
     * approved/rejected, and a note explaining the change is mandatory.
     */
    public function update(Request $request, ProcurementRequest $procurement)
    {
        abort_unless(auth()->user()->can('approve-procurement-officer'), 403);
        $this->authorizeBuilding($procurement);

        if (! $procurement->canOfficerModify()) {
            return back()->with('error', 'This request can no longer be modified.');
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'justification'  => 'nullable|string|max:1000',
            'notes'          => 'nullable|string|max:1000',
            'vendor_id'      => 'nullable|exists:vendors,id',
            'supplier_name'  => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_bank_name'      => 'nullable|string|max:100',
            'supplier_account_number' => 'nullable|string|max:20',
            'supplier_account_name'   => 'nullable|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.track_stock' => 'boolean',
            'modification_note'   => 'required|string|max:1000',
        ]);

        $before = [
            'total_amount' => $procurement->total_amount,
            'items'        => $procurement->items->map(fn ($i) => "{$i->name} ×{$i->quantity} @ " . ($i->unit_price ?? '—'))->all(),
        ];

        DB::transaction(function () use ($procurement, $validated) {
            $procurement->update([
                'title'          => $validated['title'],
                'justification'  => $validated['justification'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'vendor_id'      => $validated['vendor_id'] ?? null,
                'supplier_name'  => $validated['supplier_name'] ?? null,
                'supplier_phone' => $validated['supplier_phone'] ?? null,
                'supplier_email' => $validated['supplier_email'] ?? null,
                'supplier_bank_name'      => $validated['supplier_bank_name'] ?? null,
                'supplier_account_number' => $validated['supplier_account_number'] ?? null,
                'supplier_account_name'   => $validated['supplier_account_name'] ?? null,
            ]);

            // Replace the line items wholesale (add/remove/edit in one pass).
            $procurement->items()->delete();
            $total = 0;
            foreach ($validated['items'] as $item) {
                $unitPrice = $item['unit_price'] ?? null;
                $lineTotal = $unitPrice === null ? null : $item['quantity'] * $unitPrice;
                $total += $lineTotal ?? 0;
                $procurement->items()->create([
                    'name'        => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $unitPrice,
                    'total_price' => $lineTotal,
                    'track_stock' => $item['track_stock'] ?? true,
                ]);
            }
            $procurement->update(['total_amount' => $total]);
        });

        $procurement->refresh()->loadMissing('items');

        AuditLog::log('procurement.modified', $procurement, $before, [
            'note'         => $validated['modification_note'],
            'total_amount' => $procurement->total_amount,
            'items'        => $procurement->items->map(fn ($i) => "{$i->name} ×{$i->quantity} @ " . ($i->unit_price ?? '—'))->all(),
            'by'           => auth()->id(),
        ]);

        $this->notifyProcurement(
            $procurement,
            'Procurement Request Updated',
            "The Procurement Officer updated \"{$procurement->title}\". Note: {$validated['modification_note']}",
            [],
            [$procurement->submitted_by],
        );

        return back()->with('success', 'Request updated.');
    }

    public function show(ProcurementRequest $procurement)
    {
        abort_unless(auth()->user()->can('view-procurement'), 403);

        $this->authorizeBuilding($procurement);

        $procurement->load([
            'building', 'vendor', 'items',
            'submittedBy', 'officerApprovedBy', 'accountantApprovedBy',
            'ceoApprovedBy', 'purchasedBy', 'receiptConfirmedBy',
            'documents.uploadedBy',
            'relatedRequest:id,reference,title,status',
            'continuations:id,related_request_id,reference,title,status',
        ]);

        $user      = auth()->user();
        $canModify = $user->can('approve-procurement-officer') && $procurement->canOfficerModify();

        return Inertia::render('Admin/Procurement/Show', [
            'procurement' => array_merge($procurement->toArray(), [
                'submitted_by_id' => $procurement->submitted_by,
                'status_label'            => $procurement->statusLabel(),
                'can_officer_approve'     => $procurement->canOfficerApprove(),
                'can_officer_modify'      => $canModify,
                'has_unpriced_items'      => $procurement->hasUnpricedItems(),
                'can_accountant_approve'  => $procurement->canAccountantApprove(),
                'can_ceo_approve'         => $procurement->canCeoApprove(),
                'can_mark_purchased'      => $procurement->canMarkPurchased(),
                'can_confirm_receipt'     => $procurement->canConfirmReceipt(),
                'has_receipt'             => $procurement->hasReceipt(),
                'can_upload_documents'    => $user->can('view-procurement'),
            ]),
            // Vendors power the supplier picker in the officer's modify modal.
            'vendors' => $canModify
                ? Vendor::where('is_active', true)->orderBy('name')
                    ->get(['id', 'name', 'phone', 'email', 'bank_name', 'bank_account_number', 'bank_account_name'])
                : [],
        ]);
    }

    public function approve(Request $request, ProcurementRequest $procurement)
    {
        $this->authorizeBuilding($procurement);
        $user = auth()->user();

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes'  => 'nullable|string|max:500',
        ]);

        if ($validated['action'] === 'reject') {
            $procurement->update([
                'status'           => 'rejected',
                'rejection_reason' => $validated['notes'],
                'rejected_by_role' => $user->getRoleNames()->first(),
            ]);

            AuditLog::log('procurement.rejected', $procurement, null, ['reason' => $validated['notes']]);

            $rejectionReason = $validated['notes'] ?? 'No reason provided.';
            $this->notifyProcurement(
                $procurement,
                'Procurement Request Rejected',
                "\"{$procurement->title}\" has been rejected. Reason: {$rejectionReason}",
                [],
                [$procurement->submitted_by],
            );

            return back()->with('success', 'Request rejected.');
        }

        if ($procurement->canOfficerApprove() && $user->can('approve-procurement-officer')) {
            // Prices must be set before the request moves to the accountant, or
            // the rest of the chain would approve an incomplete amount.
            if ($procurement->loadMissing('items')->hasUnpricedItems()) {
                return back()->with('error', 'Set a price for every item before approving.');
            }

            $procurement->update([
                'status'              => 'officer_approved',
                'officer_approved_by' => $user->id,
                'officer_approved_at' => now(),
            ]);

            $this->notifyProcurement(
                $procurement,
                'Procurement Awaiting Accountant Approval',
                "Procurement request \"{$procurement->title}\" has been reviewed by the Procurement Officer and needs the accountant's approval.",
                ['accountant'],
            );

        } elseif ($procurement->canAccountantApprove() && $user->can('approve-procurement-accountant')) {
            $procurement->update([
                'status'                 => 'accountant_approved',
                'accountant_approved_by' => $user->id,
                'accountant_approved_at' => now(),
            ]);

            $this->notifyProcurement(
                $procurement,
                'Procurement Awaiting CEO Approval',
                "Procurement request \"{$procurement->title}\" has been approved by the accountant and needs your sign-off.",
                ['ceo'],
            );

        } elseif ($procurement->canCeoApprove() && $user->can('approve-procurement-ceo')) {
            $procurement->update([
                'status'          => 'ceo_approved',
                'ceo_approved_by' => $user->id,
                'ceo_approved_at' => now(),
            ]);

            $this->notifyProcurement(
                $procurement,
                'Procurement Approved - Ready to Purchase',
                "CEO has approved \"{$procurement->title}\". Proceed with purchase.",
                ['head-of-procurement', 'manager', 'accountant'],
            );

        } elseif ($procurement->canMarkPurchased() && $user->can('purchase-procurement')) {
            $procurement->update([
                'status'       => 'purchased',
                'purchased_by' => $user->id,
                'purchased_at' => now(),
            ]);

            $this->notifyProcurement(
                $procurement,
                'Procurement Purchased',
                "\"{$procurement->title}\" has been purchased and is awaiting receipt confirmation.",
                ['manager'],
            );

        } elseif ($procurement->canConfirmReceipt() && $user->can('confirm-procurement-receipt')) {
            // A purchase receipt is encouraged but optional — confirmation is not blocked on it.
            $procurement->load('items');

            foreach ($procurement->items as $item) {
                // Skip services, labour, or one-off assets that aren't inventory
                if (! $item->track_stock) {
                    continue;
                }

                $stockItem = StockItem::whereRaw('LOWER(name) = ?', [strtolower($item->name)])
                    ->where('building_id', $procurement->building_id)
                    ->first();

                if ($stockItem) {
                    $quantityBefore = $stockItem->quantity;
                    $quantityAfter  = $quantityBefore + $item->quantity;
                    $stockItem->update(['quantity' => $quantityAfter]);
                } else {
                    $stockItem = StockItem::create([
                        'building_id'         => $procurement->building_id,
                        'name'                => $item->name,
                        'category'            => null,
                        'unit'                => 'unit',
                        'quantity'            => $item->quantity,
                        'low_stock_threshold' => 5,
                        'notes'               => 'Auto-created from procurement #' . $procurement->reference,
                        'created_by'          => $user->id,
                    ]);
                    $quantityBefore = 0;
                    $quantityAfter  = $item->quantity;
                }

                StockLog::create([
                    'stock_item_id'   => $stockItem->id,
                    'logged_by'       => $user->id,
                    'type'            => 'restock',
                    'quantity'        => $item->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after'  => $quantityAfter,
                    'reason'          => 'Received via procurement: ' . $procurement->reference,
                ]);
            }

            $procurement->update([
                'status'               => 'completed',
                'receipt_confirmed_by' => $user->id,
                'receipt_confirmed_at' => now(),
            ]);

            // Notify the original submitter (and admin observers) that it's complete
            $this->notifyProcurement(
                $procurement,
                'Procurement Completed',
                "\"{$procurement->title}\" has been received and marked complete.",
                [],
                [$procurement->submitted_by],
            );
        } else {
            return back()->with('error', 'You are not authorized to perform this action.');
        }

        AuditLog::log('procurement.status_updated', $procurement, ['status' => $procurement->getOriginal('status')], ['status' => $procurement->status]);

        return back()->with('success', 'Request updated successfully.');
    }

    public function destroy(ProcurementRequest $procurement)
    {
        $this->authorizeBuilding($procurement);

        if (!in_array($procurement->status, ['pending', 'rejected'])) {
            return back()->with('error', 'Only pending or rejected requests can be deleted.');
        }

        AuditLog::log('procurement.deleted', $procurement, ['reference' => $procurement->reference, 'title' => $procurement->title], null);

        $procurement->items()->delete();
        $procurement->delete();

        return redirect()->route('manage.procurement.index')
            ->with('success', 'Request deleted.');
    }

    private function authorizeBuilding(ProcurementRequest $procurement): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($procurement->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }

    /**
     * Send a procurement notification to the given roles plus super-admin observers,
     * and any explicit extra user IDs (e.g. the submitter). The acting user is never
     * notified of their own action, and recipients are de-duplicated.
     */
    private function notifyProcurement(
        ProcurementRequest $procurement,
        string $title,
        string $message,
        array $roles = [],
        array $extraUserIds = [],
    ): void {
        $recipients = collect();

        if (! empty($roles)) {
            $recipients = $recipients->merge(
                NotificationService::getUsersByRoles($roles, $procurement->building_id)
            );
        }

        // Super-admins always observe procurement activity (oversight on spend)
        $recipients = $recipients->merge(
            User::role('super-admin')->where('is_active', true)->get()
        );

        if (! empty($extraUserIds)) {
            $recipients = $recipients->merge(User::whereIn('id', $extraUserIds)->get());
        }

        $recipients = $recipients->unique('id')->reject(fn ($u) => $u->id === auth()->id());

        if ($recipients->isNotEmpty()) {
            NotificationService::send($recipients, new ProcurementStatusNotification($procurement, $title, $message));
        }
    }
}
