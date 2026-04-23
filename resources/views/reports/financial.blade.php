<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; padding: 40px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 32px; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .card-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
        .card-value { font-size: 20px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; padding: 6px 8px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        .income td { color: #059669; }
        .expense td { color: #dc2626; }
        .amount { text-align: right; font-weight: 500; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
        @media print { body { padding: 20px; } }
    </style>
</head>
<body>
<h1>CityStake — Financial Transactions</h1>
<p class="meta">
    Period: {{ $start->format('d M Y') }} — {{ $end->format('d M Y') }} &nbsp;·&nbsp;
    Generated: {{ now()->format('d M Y, H:i') }}
</p>

<div class="summary">
    <div class="card">
        <div class="card-label">Total Income</div>
        <div class="card-value" style="color:#059669">₦{{ number_format($income, 0) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Total Expenses</div>
        <div class="card-value" style="color:#dc2626">₦{{ number_format($expenses, 0) }}</div>
    </div>
    <div class="card">
        <div class="card-label">Net</div>
        <div class="card-value" style="color:{{ $net >= 0 ? '#059669' : '#dc2626' }}">
            ₦{{ number_format(abs($net), 0) }} {{ $net < 0 ? '(Loss)' : '' }}
        </div>
    </div>
</div>

<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Category</th>
        <th>Description</th>
        <th>Building</th>
        <th>Method</th>
        <th>Reference</th>
        <th class="amount">Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transactions as $t)
        <tr class="{{ $t->type }}">
            <td>{{ $t->transaction_date->format('d M Y') }}</td>
            <td>{{ ucfirst($t->type) }}</td>
            <td>{{ \App\Models\FinancialTransaction::categoryLabels()[$t->category] ?? $t->category }}</td>
            <td>{{ $t->description }}</td>
            <td>{{ $t->building?->name }}</td>
            <td>{{ $t->payment_method ? ucfirst(str_replace('_', ' ', $t->payment_method)) : '—' }}</td>
            <td>{{ $t->payment_reference ?? '—' }}</td>
            <td class="amount">{{ $t->type === 'expense' ? '-' : '' }}₦{{ number_format($t->amount, 0) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">CityStake Property Management · Confidential · {{ now()->format('d M Y') }}</div>

</body>
</html>
