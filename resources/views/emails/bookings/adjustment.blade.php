@extends('emails.layout')

@section('content')
    <h1 class="email-title">A Goodwill Adjustment Has Been Applied</h1>

    <p class="email-text">Dear {{ $adjustment->booking->guest_name }},</p>

    <p class="email-text">
        We sincerely apologise for any inconvenience you experienced during your stay at
        <strong>{{ $adjustment->booking->building->name }}</strong>. As a gesture of goodwill,
        we have applied a refund to your booking.
    </p>

    <div class="booking-card">
        <div class="booking-reference">
            Booking Reference: {{ $adjustment->booking->booking_reference }}
        </div>

        <div class="booking-detail">
            <span class="booking-label">Refund Amount</span>
            <span class="booking-value">₦{{ number_format($adjustment->amount_naira, 0) }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Reason</span>
            <span class="booking-value">{{ $adjustment->reason }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Date</span>
            <span class="booking-value">{{ $adjustment->transaction_date->format('D, M j, Y') }}</span>
        </div>

        @if($adjustment->payment_reference)
            <div class="booking-detail">
                <span class="booking-label">Payment Reference</span>
                <span class="booking-value">{{ $adjustment->payment_reference }}</span>
            </div>
        @endif
    </div>

    @if($adjustment->notes)
        <div class="info-box">
            <p class="info-box-title">📝 Additional Note</p>
            <p class="info-box-text">{{ $adjustment->notes }}</p>
        </div>
    @endif

    <p class="email-text">
        The refund will be processed to your original payment method within 3–5 business days.
        If you have any questions, please don't hesitate to contact us.
    </p>

    <p class="email-text">The {{ config('app.name') }} Team</p>
@endsection
