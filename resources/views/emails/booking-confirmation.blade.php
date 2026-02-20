@extends('emails.layout')

@section('content')
    <h1 class="email-title">Booking Confirmed! 🎉</h1>

    <p class="email-text">
        Dear {{ $booking->guest_name }},
    </p>

    <p class="email-text">
        Thank you for choosing {{ config('app.name') }}! Your booking has been confirmed and we're excited to host you.
    </p>

    <div class="booking-card">
        <div class="booking-reference">
            Booking Reference: {{ $booking->booking_reference }}
        </div>

        <div class="booking-detail">
            <span class="booking-label">Property</span>
            <span class="booking-value">{{ $booking->unitType->name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Location</span>
            <span class="booking-value">{{ $booking->building->name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-in</span>
            <span class="booking-value">{{ $booking->check_in->format('D, M j, Y') }} (After 2:00 PM)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-out</span>
            <span class="booking-value">{{ $booking->check_out->format('D, M j, Y') }} (Before 12:00 PM)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Duration</span>
            <span class="booking-value">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Guests</span>
            <span class="booking-value">{{ $booking->guests }} guest{{ $booking->guests > 1 ? 's' : '' }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Unit Number</span>
            <span class="booking-value">Unit {{ $booking->unit->unit_number }} ({{ $booking->unit->floor }} Floor)</span>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
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

    <div class="info-box">
        <p class="info-box-title">📍 Property Address</p>
        <p class="info-box-text">
            {{ $booking->building->address }}, {{ $booking->building->city }}
        </p>
    </div>

    @if($booking->special_requests)
        <div class="info-box" style="background-color: #fef3c7; border-color: #f59e0b;">
            <p class="info-box-title" style="color: #92400e;">📝 Your Special Requests</p>
            <p class="info-box-text" style="color: #92400e;">
                {{ $booking->special_requests }}
            </p>
        </div>
    @endif

    <center>
        <a href="{{ route('bookings.show', $booking->id) }}" class="button">View Booking Details</a>
    </center>

    <p class="email-text">
        If you have any questions or need assistance, please don't hesitate to contact us.
    </p>

    <p class="email-text">
        We look forward to welcoming you!<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
