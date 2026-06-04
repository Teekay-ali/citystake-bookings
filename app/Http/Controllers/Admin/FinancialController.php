<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Building;
use App\Models\FinancialTransaction;
use App\Models\MaintenanceReport;
use App\Models\ProcurementRequest;
use App\Traits\ScopedByBuilding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialController extends Controller
{
    use ScopedByBuilding;

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('view-financials'), 403);

        $user        = auth()->user();
        $buildings   = $this->accessibleBuildings()->get(['id', 'name']);
        $buildingIds = $user->hasGlobalAccess()
            ? $buildings->pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $buildingId = $request->input('building_id');
        if ($buildingId && !in_array((int) $buildingId, $buildingIds)) {
            $buildingId = null;
        }
        $scopedIds = $buildingId ? [(int) $buildingId] : $buildingIds;

        // Pending payment queue - approved maintenance + procurement
        $pendingMaintenance = MaintenanceReport::whereIn('building_id', $scopedIds)
            ->where('status', 'ceo_approved')
            ->with(['building', 'vendor', 'submittedBy'])
            ->latest()
            ->get();

        $pendingProcurement = ProcurementRequest::whereIn('building_id', $scopedIds)
            ->where('status', 'ceo_approved')
            ->with(['building', 'vendor', 'submittedBy'])
            ->latest()
            ->get();

        // Add this block before the existing period/year/month lines:
        $request->validate([
            'year'    => 'nullable|integer|digits:4|min:2020|max:' . (now()->year + 1),
            'month'   => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'date'    => 'nullable|date',
            'period'  => 'nullable|in:daily,monthly,quarterly,yearly',
        ]);

        // Transaction ledger
        $period     = $request->input('period', 'monthly');
        $year       = $request->input('year', now()->year);
        $month      = $request->input('month', now()->month);
        $quarter    = $request->input('quarter', ceil(now()->month / 3));
        $date       = $request->input('date', now()->toDateString());

        [$startDate, $endDate] = $this->resolveDateRange($period, $year, $month, $quarter, $date);

        $transactions = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->with(['building', 'recordedBy'])
            ->latest('transaction_date')
            ->paginate(30)
            ->withQueryString();

        // Summary from transactions
        $income   = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $expenses = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        // 12-month trend
        $trend = $this->getMonthlyTrend($scopedIds);

        return Inertia::render('Admin/Financial/Index', [
            'pendingMaintenance' => $pendingMaintenance,
            'pendingProcurement' => $pendingProcurement,
            'transactions'       => $transactions,
            'summary'            => [
                'total_income'   => (float) $income,
                'total_expenses' => (float) $expenses,
                'net_profit'     => (float) ($income - $expenses),
                'profit_margin'  => $income > 0 ? round((($income - $expenses) / $income) * 100, 1) : 0,
                'pending_count'  => $pendingMaintenance->count() + $pendingProcurement->count(),
            ],
            'trend'            => $trend,
            'buildings'        => $buildings,
            'categoryLabels'   => FinancialTransaction::categoryLabels(),
            'filters'          => [
                'period'      => $period,
                'building_id' => $buildingId,
                'year'        => $year,
                'month'       => $month,
                'quarter'     => $quarter,
                'date'        => $date,
                'type'        => $request->type,
                'category'    => $request->category,
            ],
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ],
        ]);
    }

    public function payExpense(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['maintenance', 'procurement']), 422);

        $validated = $request->validate([
            'payment_method'   => 'required|in:cash,bank_transfer,pos,paystack,other',
            'payment_reference'=> 'nullable|string|max:255',
            'bank_name'        => 'nullable|string|max:255',
            'amount'           => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        // Resolve building scope before fetching the record to avoid existence probing
        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess() ? null : ($user->accessibleBuildingIds() ?? []);

        if ($type === 'maintenance') {
            abort_unless(auth()->user()->can('pay-maintenance'), 403);
            $record = MaintenanceReport::findOrFail($id);

            if ($buildingIds !== null) {
                abort_unless(in_array($record->building_id, $buildingIds), 403);
            }

            $record->update([
                'status'          => 'completed',
                'payment_made_by' => auth()->id(),
                'payment_made_at' => now(),
                'actual_cost'     => $validated['amount'],
            ]);

            $description = "Maintenance: {$record->title} - {$record->building->name}";
            $category    = 'maintenance';
            $buildingId  = $record->building_id;
            $refType     = MaintenanceReport::class;
            $refId       = $record->id;

        } else {
            abort_unless(auth()->user()->can('purchase-procurement'), 403);
            $record = ProcurementRequest::findOrFail($id);

            if ($buildingIds !== null) {
                abort_unless(in_array($record->building_id, $buildingIds), 403);
            }

            $record->update([
                'status'       => 'purchased',
                'purchased_by' => auth()->id(),
                'purchased_at' => now(),
            ]);

            $description = "Procurement: {$record->title} - {$record->building->name}";
            $category    = 'procurement';
            $buildingId  = $record->building_id;
            $refType     = ProcurementRequest::class;
            $refId       = $record->id;
        }

        FinancialTransaction::create([
            'building_id'       => $buildingId,
            'recorded_by'       => auth()->id(),
            'type'              => 'expense',
            'category'          => $category,
            'reference_type'    => $refType,
            'reference_id'      => $refId,
            'description'       => $description,
            'amount'            => $validated['amount'],
            'payment_method'    => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'bank_name'         => $validated['bank_name'] ?? null,
            'transaction_date'  => $validated['transaction_date'],
            'notes'             => $validated['notes'] ?? null,
        ]);

        AuditLog::log('expense.paid', null,
            null,
            [
                'type'             => $type,
                'record_id'        => $id,
                'amount'           => $validated['amount'],
                'payment_method'   => $validated['payment_method'],
                'transaction_date' => $validated['transaction_date'],
            ]
        );

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function storeManual(Request $request)
    {
        abort_unless(auth()->user()->can('manage-financials'), 403);

        $validated = $request->validate([
            'building_id'       => 'required|exists:buildings,id',
            'type'              => 'required|in:income,expense',
            'description'       => 'required|string|max:255',
            'amount'            => 'required|numeric|min:0.01',
            'payment_method'    => 'nullable|in:cash,bank_transfer,pos,paystack,other',
            'payment_reference' => 'nullable|string|max:255',
            'bank_name'         => 'nullable|string|max:255',
            'transaction_date'  => 'required|date',
            'notes'             => 'nullable|string|max:500',
        ]);

        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess()
            ? Building::pluck('id')->toArray()
            : ($user->accessibleBuildingIds() ?? []);

        abort_unless(in_array((int) $validated['building_id'], $buildingIds), 403);

        $transaction = FinancialTransaction::create([
            ...$validated,
            'category'    => $validated['type'] === 'income' ? 'manual_income' : 'manual_expense',
            'recorded_by' => auth()->id(),
        ]);

        AuditLog::log('financial.manual_entry', $transaction, null, [
            'type'        => $transaction->type,
            'category'    => $transaction->category,
            'amount'      => $transaction->amount,
            'building_id' => $transaction->building_id,
            'description' => $transaction->description,
        ]);

        return back()->with('success', 'Transaction recorded.');
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->can('view-analytics'), 403);

        $user        = auth()->user();
        $buildingIds = $user->hasGlobalAccess()
            ? Building::pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $buildingId = $request->input('building_id');
        $scopedIds  = ($buildingId && in_array((int)$buildingId, $buildingIds))
            ? [(int)$buildingId] : $buildingIds;

        $period  = $request->input('period', 'monthly');
        $year    = $request->input('year', now()->year);
        $month   = $request->input('month', now()->month);
        $quarter = $request->input('quarter', ceil(now()->month / 3));
        $date    = $request->input('date', now()->toDateString());
        $format  = $request->input('format', 'csv');

        [$startDate, $endDate] = $this->resolveDateRange($period, $year, $month, $quarter, $date);

        $transactions = FinancialTransaction::whereIn('building_id', $scopedIds)
            ->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with(['building', 'recordedBy'])
            ->latest('transaction_date')
            ->get();

        if ($format === 'csv') {
            return $this->exportCsv($transactions, $startDate, $endDate);
        }

        return $this->exportPdf($transactions, $startDate, $endDate);
    }

    public function deposits(Request $request)
    {
        abort_unless(auth()->user()->can('view-financials'), 403);

        $user        = auth()->user();
        $buildings   = $this->accessibleBuildings()->get(['id', 'name']);
        $buildingIds = $user->hasGlobalAccess()
            ? $buildings->pluck('id')->toArray()
            : $user->accessibleBuildingIds();

        $filter = $request->input('filter', 'outstanding');

        $query = Booking::whereIn('building_id', $buildingIds)
            ->where('caution_fee', '>', 0)
            ->with(['building:id,name', 'unitType:id,name', 'unit:id,unit_number'])
            ->when($filter === 'outstanding',
                fn($q) => $q->where('caution_fee_refunded', false)->where('caution_refund_requested', false))
            ->when($filter === 'pending_refund',
                fn($q) => $q->where('caution_refund_requested', true)->where('caution_fee_refunded', false))
            ->when($filter === 'refunded',
                fn($q) => $q->where('caution_fee_refunded', true))
            ->latest('check_out');

        $deposits = $query->paginate(30)->withQueryString();

        $summary = [
            'total_outstanding' => Booking::whereIn('building_id', $buildingIds)
                ->where('caution_fee', '>', 0)->where('caution_fee_refunded', false)->sum('caution_fee'),
            'total_refunded' => Booking::whereIn('building_id', $buildingIds)
                ->where('caution_fee', '>', 0)->where('caution_fee_refunded', true)->sum('caution_fee'),
            'count_outstanding' => Booking::whereIn('building_id', $buildingIds)
                ->where('caution_fee', '>', 0)->where('caution_fee_refunded', false)->count(),
            'count_pending_refund' => Booking::whereIn('building_id', $buildingIds)
                ->where('caution_fee', '>', 0)
                ->where('caution_refund_requested', true)
                ->where('caution_fee_refunded', false)
                ->count(),
        ];

        return Inertia::render('Admin/Financial/Deposits', [
            'deposits'  => $deposits,
            'summary'   => $summary,
            'buildings' => $buildings,
            'filters'   => ['filter' => $filter],
        ]);
    }

    // ── Private helpers ────────────────────────────────────────

    private function resolveDateRange(string $period, int $year, int $month, int $quarter, string $date): array
    {
        return match($period) {
            'daily'     => [Carbon::parse($date)->startOfDay(), Carbon::parse($date)->endOfDay()],
            'monthly'   => [Carbon::create($year, $month)->startOfMonth(), Carbon::create($year, $month)->endOfMonth()],
            'quarterly' => [Carbon::create($year, ($quarter - 1) * 3 + 1)->startOfMonth(), Carbon::create($year, $quarter * 3)->endOfMonth()],
            'yearly'    => [Carbon::create($year)->startOfYear(), Carbon::create($year)->endOfYear()],
            default     => [Carbon::create($year, $month)->startOfMonth(), Carbon::create($year, $month)->endOfMonth()],
        };
    }

    private function getMonthlyTrend(array $buildingIds): array
    {
        $rows = FinancialTransaction::whereIn('building_id', $buildingIds)
            ->where('transaction_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw('YEAR(transaction_date) as yr, MONTH(transaction_date) as mo, type, SUM(amount) as total')
            ->groupBy('yr', 'mo', 'type')
            ->get()
            ->groupBy(fn($r) => $r->yr . '-' . str_pad($r->mo, 2, '0', STR_PAD_LEFT));

        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $key   = $date->format('Y-m');
            $month = $rows->get($key, collect());

            $income   = (float) ($month->firstWhere('type', 'income')?->total ?? 0);
            $expenses = (float) ($month->firstWhere('type', 'expense')?->total ?? 0);

            $trend[] = [
                'month'    => $date->format('M Y'),
                'income'   => $income,
                'expenses' => $expenses,
                'net'      => $income - $expenses,
            ];
        }
        return $trend;
    }

    private function exportCsv($transactions, $start, $end)
    {
        $filename = 'transactions-' . $start->format('Y-m-d') . '-to-' . $end->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions, $start, $end) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['CityStake Financial Transactions']);
            fputcsv($handle, ['Period', $start->format('d M Y') . ' - ' . $end->format('d M Y')]);
            fputcsv($handle, ['Generated', now()->format('d M Y H:i')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Date', 'Type', 'Category', 'Description', 'Building', 'Method', 'Reference', 'Amount (₦)', 'Recorded By']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->transaction_date->format('d M Y'),
                    ucfirst($t->type),
                    FinancialTransaction::categoryLabels()[$t->category] ?? $t->category,
                    $t->description,
                    $t->building?->name,
                    $t->payment_method ? ucfirst(str_replace('_', ' ', $t->payment_method)) : '',
                    $t->payment_reference ?? '',
                    number_format($t->amount, 2),
                    $t->recordedBy?->name,
                ]);
            }

            $income   = $transactions->where('type', 'income')->sum('amount');
            $expenses = $transactions->where('type', 'expense')->sum('amount');
            fputcsv($handle, []);
            fputcsv($handle, ['', '', '', '', '', '', 'Total Income',   number_format($income, 2)]);
            fputcsv($handle, ['', '', '', '', '', '', 'Total Expenses', number_format($expenses, 2)]);
            fputcsv($handle, ['', '', '', '', '', '', 'Net',            number_format($income - $expenses, 2)]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPdf($transactions, $start, $end)
    {
        $income   = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $net      = $income - $expenses;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.financial', compact(
            'transactions', 'income', 'expenses', 'net', 'start', 'end'
        ))->setPaper('a4', 'landscape');

        $filename = 'transactions-' . $start->format('Y-m-d') . '-to-' . $end->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

}
