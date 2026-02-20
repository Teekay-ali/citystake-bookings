@extends('emails.layout')

@section('content')
    <h1 class="email-title">New Booking Received 🎉</h1>

    <p class="email-text">
        A new booking has been confirmed on the platform.
    </p>

    <div class="booking-card">
        <div class="booking-reference">
            Booking Reference: {{ $booking->booking_reference }}
        </div>

        <div class="booking-detail">
            <span class="booking-label">Guest Name</span>
            <span class="booking-value">{{ $booking->guest_name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Guest Email</span>
            <span class="booking-value">{{ $booking->guest_email }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Guest Phone</span>
            <span class="booking-value">{{ $booking->guest_phone }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Property</span>
            <span class="booking-value">{{ $booking->unitType->name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Building</span>
            <span class="booking-value">{{ $booking->building->name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Unit</span>
            <span class="booking-value">Unit {{ $booking->unit->unit_number }} ({{ $booking->unit->floor }} Floor)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-in</span>
            <span class="booking-value">{{ $booking->check_in->format('D, M j, Y') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-out</span>
            <span class="booking-value">{{ $booking->check_out->format('D, M j, Y') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Duration</span>
            <span class="booking-value">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Guests</span>
            <span class="booking-value">{{ $booking->guests }}</span>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Subtotal</span>
                <span>₦{{ number_format($booking->subtotal, 0) }}</span>
            </div>
            <div class="total-row">
                <span>Cleaning fee</span>
                <span>₦{{ number_format($booking->cleaning_fee, 0) }}</span>
            </div>
            <div class="total-row">
                <span>Service charge</span>
                <span>₦{{ number_format($booking->service_charge, 0) }}</span>
            </div>
            <div class="total-row">
                <span>Total Amount</span>
                <span>₦{{ number_format($booking->total_amount, 0) }}</span>
            </div>
        </div>
    </div>

    @if($booking->special_requests)
        <div class="info-box" style="background-color: #fef3c7; border-color: #f59e0b;">
            <p class="info-box-title" style="color: #92400e;">📝 Special Requests</p>
            <p class="info-box-text" style="color: #92400e;">
                {{ $booking->special_requests }}
            </p>
        </div>
    @endif

    <div class="info-box" style="background-color: {{ $booking->payment_status === 'paid' ? '#dcfce7' : '#fee2e2' }}; border-color: {{ $booking->payment_status === 'paid' ? '#22c55e' : '#ef4444' }};">
        <p class="info-box-title" style="color: {{ $booking->payment_status === 'paid' ? '#166534' : '#991b1b' }};">
            💳 Payment Status
        </p>
        <p class="info-box-text" style="color: {{ $booking->payment_status === 'paid' ? '#166534' : '#991b1b' }};">
            <strong>{{ ucfirst($booking->payment_status) }}</strong>
            @if($booking->payment_method)
                via {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}
            @endif
        </p>
    </div>

    <center>
        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="button">View in Admin Panel</a>
    </center>

    <p class="email-text">
        Booking created at: {{ $booking->created_at->format('D, M j, Y g:i A') }}
    </p>
@endsection
