<?php

namespace App\Http\Controllers\Admin;

use App\Models\StockItem;
use App\Models\StockLog;
use App\Notifications\ProcurementStatusNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\ProcurementRequest;
use App\Models\Vendor;
use Illuminate\Http\Request;
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

        $requests = $query->paginate(20)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        return Inertia::render('Admin/Procurement/Index', [
            'requests'  => $requests,
            'buildings' => $buildings,
            'filters'   => $request->only(['building_id', 'status']),
            'counts'    => [
                'pending'             => ProcurementRequest::scopedToUser($user)->where('status', 'pending')->count(),
                'accountant_approved' => ProcurementRequest::scopedToUser($user)->where('status', 'accountant_approved')->count(),
                'ceo_approved'        => ProcurementRequest::scopedToUser($user)->where('status', 'ceo_approved')->count(),
                'purchased'           => ProcurementRequest::scopedToUser($user)->where('status', 'purchased')->count(),
            ],
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
            ->get(['id', 'name', 'phone', 'email']);

        return Inertia::render('Admin/Procurement/Create', [
            'buildings' => $buildings,
            'vendors'   => $vendors,
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
            'items'          => 'required|array|min:1',
            'items.*.name'       => 'required|string|max:255',
            'items.*.description'=> 'nullable|string|max:500',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $pr = ProcurementRequest::create([
            'reference'      => ProcurementRequest::generateReference(),
            'building_id'    => $validated['building_id'],
            'submitted_by'   => auth()->id(),
            'title'          => $validated['title'],
            'justification'  => $validated['justification'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'vendor_id'      => $validated['vendor_id'] ?? null,
            'supplier_name'  => $validated['supplier_name'] ?? null,
            'supplier_phone' => $validated['supplier_phone'] ?? null,
            'supplier_email' => $validated['supplier_email'] ?? null,
        ]);

        $total = 0;
        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $total += $lineTotal;
            $pr->items()->create([
                'name'        => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total_price' => $lineTotal,
            ]);
        }

        $pr->update(['total_amount' => $total]);

        return redirect()->route('manage.procurement.index')
            ->with('success', 'Procurement request submitted successfully.');
    }

    public function show(ProcurementRequest $procurement)
    {
        abort_unless(auth()->user()->can('view-procurement'), 403);

        $this->authorizeBuilding($procurement);

        $procurement->load([
            'building', 'vendor', 'items',
            'submittedBy', 'accountantApprovedBy',
            'ceoApprovedBy', 'purchasedBy', 'receiptConfirmedBy',
        ]);

        return Inertia::render('Admin/Procurement/Show', [
            'procurement' => array_merge($procurement->toArray(), [
                'status_label'            => $procurement->statusLabel(),
                'can_accountant_approve'  => $procurement->canAccountantApprove(),
                'can_ceo_approve'         => $procurement->canCeoApprove(),
                'can_mark_purchased'      => $procurement->canMarkPurchased(),
                'can_confirm_receipt'     => $procurement->canConfirmReceipt(),
            ]),
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
            return back()->with('success', 'Request rejected.');
        }

        if ($procurement->canAccountantApprove() && $user->can('approve-procurement-accountant')) {
            $procurement->update([
                'status'                 => 'accountant_approved',
                'accountant_approved_by' => $user->id,
                'accountant_approved_at' => now(),
            ]);

            $recipients = NotificationService::getUsersByRoles(['ceo'], $procurement->building_id);
            Notification::send($recipients, new ProcurementStatusNotification(
                $procurement,
                'Procurement Awaiting CEO Approval',
                "Procurement request \"{$procurement->title}\" has been approved by the accountant and needs your sign-off."
            ));

        } elseif ($procurement->canCeoApprove() && $user->can('approve-procurement-ceo')) {
            $procurement->update([
                'status'          => 'ceo_approved',
                'ceo_approved_by' => $user->id,
                'ceo_approved_at' => now(),
            ]);

            $recipients = NotificationService::getUsersByRoles(['head-of-procurement', 'accountant'], $procurement->building_id);
            Notification::send($recipients, new ProcurementStatusNotification(
                $procurement,
                'Procurement Approved — Ready to Purchase',
                "CEO has approved \"{$procurement->title}\". Proceed with purchase."
            ));

        } elseif ($procurement->canMarkPurchased() && $user->can('purchase-procurement')) {
            $procurement->update([
                'status'       => 'purchased',
                'purchased_by' => $user->id,
                'purchased_at' => now(),
            ]);

            $recipients = NotificationService::getUsersByRoles(['manager'], $procurement->building_id);
            Notification::send($recipients, new ProcurementStatusNotification(
                $procurement,
                'Procurement Purchased',
                "\"{$procurement->title}\" has been purchased and is awaiting receipt confirmation."
            ));

        } elseif ($procurement->canConfirmReceipt() && $user->can('confirm-procurement-receipt')) {
            $procurement->load('items');

            foreach ($procurement->items as $item) {
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
        } else {
            return back()->with('error', 'You are not authorized to perform this action.');
        }

        return back()->with('success', 'Request updated successfully.');
    }

    public function destroy(ProcurementRequest $procurement)
    {
        $this->authorizeBuilding($procurement);

        if (!in_array($procurement->status, ['pending', 'rejected'])) {
            return back()->with('error', 'Only pending or rejected requests can be deleted.');
        }

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
}
