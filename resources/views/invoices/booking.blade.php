<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $booking->booking_reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #111827; background: #fff; padding: 48px; }

        .header { display: table; width: 100%; margin-bottom: 40px; }
        .header-left { display: table-cell; vertical-align: top; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }

        .brand { font-size: 26px; font-weight: 700; color: #111827; letter-spacing: -0.5px; }
        .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }

        .invoice-label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.08em; }
        .invoice-ref { font-size: 22px; font-weight: 700; color: #111827; margin-top: 4px; }
        .invoice-date { font-size: 12px; color: #6b7280; margin-top: 4px; }

        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }

        .two-col { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .col-right { text-align: right; }

        .section-label { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
        .detail-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .detail-line { font-size: 12px; color: #6b7280; margin-bottom: 2px; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-paid { background: #dcfce7; color: #166534; }

        .stay-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 28px 0; }
        .stay-row { display: table; width: 100%; margin-bottom: 10px; }
        .stay-row:last-child { margin-bottom: 0; }
        .stay-key { display: table-cell; font-size: 12px; color: #6b7280; width: 40%; }
        .stay-val { display: table-cell; font-size: 12px; font-weight: 600; color: #111827; }

        .line-items { width: 100%; border-collapse: collapse; margin: 28px 0; }
        .line-items th { font-size: 10px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.07em; padding: 0 0 10px 0; border-bottom: 2px solid #e5e7eb; text-align: left; }
        .line-items th.right { text-align: right; }
        .line-items td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; vertical-align: top; }
        .line-items td.right { text-align: right; }
        .line-items .total-row td { border-bottom: none; border-top: 2px solid #111827; padding-top: 14px; font-size: 15px; font-weight: 700; color: #111827; }

        @if($booking->discount_amount > 0)
        .discount-row td { color: #059669; }
        @endif

        .footer { margin-top: 48px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .footer-text { font-size: 11px; color: #9ca3af; line-height: 1.6; }
        .footer-text strong { color: #6b7280; }

        .watermark-paid {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px; font-weight: 900;
            color: rgba(22, 163, 74, 0.06);
            text-transform: uppercase; letter-spacing: 10px;
            white-space: nowrap; pointer-events: none;
        }
    </style>
</head>
<body>

<div class="watermark-paid">PAID</div>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <div class="brand">CityStake</div>
        <div class="brand-sub">Premium Apartments, Abuja</div>
        <div style="margin-top: 12px;">
            <div class="detail-line">{{ $booking->building->address ?? '' }}</div>
            <div class="detail-line">{{ $booking->building->city ?? 'Abuja' }}, Nigeria</div>
            <div class="detail-line">bookings@citystake.net</div>
        </div>
    </div>
    <div class="header-right">
        <div class="invoice-label">Invoice</div>
        <div class="invoice-ref">{{ $booking->booking_reference }}</div>
        <div class="invoice-date">Issued: {{ $booking->paid_at ? $booking->paid_at->format('d M Y') : now()->format('d M Y') }}</div>
        <div style="margin-top: 10px;">
            <span class="status-badge status-paid">Paid</span>
        </div>
    </div>
</div>

<hr class="divider">

<!-- Bill To / Property -->
<div class="two-col">
    <div class="col">
        <div class="section-label">Billed To</div>
        <div class="detail-name">{{ $booking->guest_name }}</div>
        <div class="detail-line">{{ $booking->guest_email }}</div>
        <div class="detail-line">{{ $booking->guest_phone }}</div>
    </div>
    <div class="col col-right">
        <div class="section-label">Property</div>
        <div class="detail-name">{{ $booking->unitType->name }}</div>
        <div class="detail-line">{{ $booking->building->name }}</div>
        <div class="detail-line">Unit {{ $booking->unit->unit_number }}{{ $booking->unit->floor ? ', ' . $booking->unit->floor . ' Floor' : '' }}</div>
    </div>
</div>

<!-- Stay Details -->
<div class="stay-box">
    <div class="stay-row">
        <span class="stay-key">Check-in</span>
        <span class="stay-val">{{ $booking->check_in->format('D, d M Y') }} &nbsp;(After 2:00 PM)</span>
    </div>
    <div class="stay-row">
        <span class="stay-key">Check-out</span>
        <span class="stay-val">{{ $booking->check_out->format('D, d M Y') }} &nbsp;(Before 12:00 PM)</span>
    </div>
    <div class="stay-row">
        <span class="stay-key">Duration</span>
        <span class="stay-val">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
    </div>
    <div class="stay-row">
        <span class="stay-key">Guests</span>
        <span class="stay-val">{{ $booking->guests }} guest{{ $booking->guests > 1 ? 's' : '' }}</span>
    </div>
    <div class="stay-row">
        <span class="stay-key">Payment Method</span>
        <span class="stay-val">{{ $booking->payment_method ? ucfirst(str_replace('_', ' ', $booking->payment_method)) : 'Online Payment' }}</span>
    </div>
</div>

<!-- Line Items -->
<table class="line-items">
    <thead>
    <tr>
        <th>Description</th>
        <th class="right">Amount</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            <strong>{{ $booking->unitType->name }}</strong><br>
            <span style="font-size:11px;color:#9ca3af;">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }} × ₦{{ number_format($booking->subtotal / $booking->nights, 0) }}/night</span>
        </td>
        <td class="right">₦{{ number_format($booking->subtotal, 0) }}</td>
    </tr>
    @if($booking->cleaning_fee > 0)
        <tr>
            <td>Cleaning Fee</td>
            <td class="right">₦{{ number_format($booking->cleaning_fee, 0) }}</td>
        </tr>
    @endif
    @if($booking->service_charge > 0)
        <tr>
            <td>Service Charge</td>
            <td class="right">₦{{ number_format($booking->service_charge, 0) }}</td>
        </tr>
    @endif
    @if($booking->discount_amount > 0)
        <tr class="discount-row">
            <td>Discount{{ $booking->discount_type ? ' (' . ucfirst(str_replace('_', ' ', $booking->discount_type)) . ')' : '' }}</td>
            <td class="right">−₦{{ number_format($booking->discount_amount, 0) }}</td>
        </tr>
    @endif
    <tr class="total-row">
        <td>Total Amount Paid</td>
        <td class="right">₦{{ number_format($booking->total_amount, 0) }}</td>
    </tr>
    </tbody>
</table>

<!-- Footer -->
<div class="footer">
    <div class="footer-text">
        <strong>Payment Reference:</strong> {{ $booking->paystack_reference ?? $booking->monnify_reference ?? $booking->payment_reference ?? 'N/A' }}<br>
        <strong>Booking Created:</strong> {{ $booking->created_at->format('d M Y, g:i A') }}<br><br>
        Thank you for choosing CityStake. This is a computer-generated invoice and requires no signature.
        For support, contact us at bookings@citystake.net.
    </div>
</div>

</body>
</html>
