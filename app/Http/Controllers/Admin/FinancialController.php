<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

        // Pending payment queue — approved maintenance + procurement
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

        $validated = $request->validate([
            'payment_method'   => 'required|in:cash,bank_transfer,pos,paystack,other',
            'payment_reference'=> 'nullable|string|max:255',
            'bank_name'        => 'nullable|string|max:255',
            'amount'           => 'required|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        if ($type === 'maintenance') {
            $record = MaintenanceReport::findOrFail($id);
            abort_unless(auth()->user()->can('pay-maintenance'), 403);

            $record->update([
                'status'          => 'completed',
                'payment_made_by' => auth()->id(),
                'payment_made_at' => now(),
                'actual_cost'     => $validated['amount'],
            ]);

            $description = "Maintenance: {$record->title} — {$record->building->name}";
            $category    = 'maintenance';
            $buildingId  = $record->building_id;
            $refType     = MaintenanceReport::class;
            $refId       = $record->id;

        } else {
            $record = ProcurementRequest::findOrFail($id);
            abort_unless(auth()->user()->can('purchase-procurement'), 403);

            $record->update([
                'status'       => 'purchased',
                'purchased_by' => auth()->id(),
                'purchased_at' => now(),
            ]);

            $description = "Procurement: {$record->title} — {$record->building->name}";
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

        FinancialTransaction::create([
            ...$validated,
            'category'    => $validated['type'] === 'income' ? 'manual_income' : 'manual_expense',
            'recorded_by' => auth()->id(),
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
        $trend = [];
        for ($i = 11; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $income   = FinancialTransaction::whereIn('building_id', $buildingIds)
                ->where('type', 'income')->whereBetween('transaction_date', [$start, $end])->sum('amount');
            $expenses = FinancialTransaction::whereIn('building_id', $buildingIds)
                ->where('type', 'expense')->whereBetween('transaction_date', [$start, $end])->sum('amount');

            $trend[] = [
                'month'    => $date->format('M Y'),
                'income'   => (float) $income,
                'expenses' => (float) $expenses,
                'net'      => (float) ($income - $expenses),
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
            fputcsv($handle, ['Period', $start->format('d M Y') . ' — ' . $end->format('d M Y')]);
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

        $html = view('reports.financial', compact(
            'transactions', 'income', 'expenses', 'net', 'start', 'end'
        ))->render();

        return response($html)->header('Content-Type', 'text/html');
    }
}
