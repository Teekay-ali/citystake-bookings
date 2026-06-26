<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\StockItem;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-stock'), 403);

        $user = auth()->user();

        $query = StockItem::with(['building', 'createdBy'])
            ->where('is_active', true);

        if (!$user->hasGlobalAccess()) {
            $query->whereIn('building_id', $user->accessibleBuildingIds());
        }

        if ($request->building_id) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Status filter: out (0), low (<= threshold but > 0), in (> threshold)
        if ($request->status === 'out') {
            $query->where('quantity', '<=', 0);
        } elseif ($request->status === 'low') {
            $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'low_stock_threshold');
        } elseif ($request->status === 'in') {
            $query->whereColumn('quantity', '>', 'low_stock_threshold');
        }

        // Sorting
        $items = match ($request->sort) {
            'name'     => $query->orderBy('name'),
            'quantity' => $query->orderByDesc('quantity'),
            'oldest'   => $query->oldest(),
            // 'lowest' (default): closest to / below threshold first
            default    => $query->orderByRaw('(quantity - low_stock_threshold) asc'),
        };

        $items = $items->paginate(20)->withQueryString();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->get(['id', 'name']);

        $categories = StockItem::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('building_id', $user->accessibleBuildingIds());
        })->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        // Status counts in a single query (scoped to building access)
        $counts = StockItem::query()
            ->where('is_active', true)
            ->when(!$user->hasGlobalAccess(), fn ($q) => $q->whereIn('building_id', $user->accessibleBuildingIds()))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN quantity > low_stock_threshold THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN quantity > 0 AND quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as `out`
            ')
            ->first();

        return Inertia::render('Admin/Stock/Index', [
            'items'      => $items,
            'buildings'  => $buildings,
            'categories' => $categories,
            'stats'      => [
                'total'    => (int) $counts->total,
                'in_stock' => (int) $counts->in_stock,
                'low'      => (int) $counts->low,
                'out'      => (int) $counts->out,
            ],
            'filters'    => $request->only(['building_id', 'category', 'status', 'search', 'sort']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $request->validate([
            'building_id'         => 'required|exists:buildings,id',
            'name'                => 'required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'unit'                => 'required|string|max:50',
            'quantity'            => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:0',
            'notes'               => 'nullable|string|max:500',
        ]);

        $item = StockItem::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('stock.item_created', $item, null, [
            'name'        => $item->name,
            'quantity'    => $item->quantity,
            'building_id' => $item->building_id,
        ]);

        // Log initial stock
        if ($validated['quantity'] > 0) {
            StockLog::create([
                'stock_item_id'   => $item->id,
                'logged_by'       => auth()->id(),
                'type'            => 'restock',
                'quantity'        => $validated['quantity'],
                'quantity_before' => 0,
                'quantity_after'  => $validated['quantity'],
                'reason'          => 'Initial stock',
            ]);
        }

        return redirect()->route('manage.stock.index')
            ->with('success', 'Stock item added successfully.');
    }

    public function show(StockItem $stock)
    {
        abort_unless(auth()->user()->can('view-stock'), 403);

        $this->authorizeBuilding($stock);

        $stock->load(['building', 'createdBy']);

        $logs = StockLog::with('loggedBy')
            ->where('stock_item_id', $stock->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Stock/Show', [
            'item' => $stock,
            'logs' => $logs,
        ]);
    }

    public function logUsage(Request $request, StockItem $stock)
    {
        $this->authorizeBuilding($stock);

        $validated = $request->validate([
            'type'     => 'required|in:usage,restock,adjustment',
            'quantity' => 'required|integer|min:1',
            'reason'   => 'nullable|string|max:255',
        ]);

        $quantityBefore = $stock->quantity;

        $change = $validated['type'] === 'usage'
            ? -$validated['quantity']
            : $validated['quantity'];

        $quantityAfter = max(0, $quantityBefore + $change);

        $stock->update(['quantity' => $quantityAfter]);

        StockLog::create([
            'stock_item_id'   => $stock->id,
            'logged_by'       => auth()->id(),
            'type'            => $validated['type'],
            'quantity'        => $change,
            'quantity_before' => $quantityBefore,
            'quantity_after'  => $quantityAfter,
            'reason'          => $validated['reason'] ?? null,
        ]);

        return back()->with('success', 'Stock updated.');
    }

    public function edit(StockItem $stock)
    {
        $this->authorizeManager();
        $this->authorizeBuilding($stock);

        $user = auth()->user();

        $buildings = Building::when(!$user->hasGlobalAccess(), function ($q) use ($user) {
            $q->whereIn('id', $user->accessibleBuildingIds());
        })->where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Admin/Stock/Edit', [
            'item'      => $stock,
            'buildings' => $buildings,
        ]);
    }

    public function update(Request $request, StockItem $stock)
    {
        $this->authorizeManager();
        $this->authorizeBuilding($stock);

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'unit'                => 'required|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
            'notes'               => 'nullable|string|max:500',
            'is_active'           => 'boolean',
        ]);

        $stock->update($validated);

        AuditLog::log('stock.item_updated', $stock,
            ['name' => $stock->getOriginal('name'), 'low_stock_threshold' => $stock->getOriginal('low_stock_threshold')],
            ['name' => $stock->name, 'low_stock_threshold' => $stock->low_stock_threshold]
        );

        return back()->with('success', 'Stock item updated.');
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()->can('manage-stock'), 403);
    }

    private function authorizeBuilding(StockItem $stock): void
    {
        $user = auth()->user();
        if (!$user->hasGlobalAccess() &&
            !in_array($stock->building_id, $user->accessibleBuildingIds())) {
            abort(403);
        }
    }
}
