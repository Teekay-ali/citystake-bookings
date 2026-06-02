@extends('emails.layout')

@section('content')
    <h1 class="email-title">Thank You for Your Stay! 👋</h1>

    <p class="email-text">Dear {{ $booking->guest_name }},</p>

    <p class="email-text">
        We hope you had a wonderful stay at {{ $booking->building->name }}. Your checkout has been confirmed and we'd love to welcome you back soon.
    </p>

    <div class="booking-card">
        <div class="booking-reference">
            Booking Reference: {{ $booking->booking_reference }}
        </div>

        <div class="booking-detail">
            <span class="booking-label">Property</span>
            <span class="booking-value">{{ $booking->unitType->name }} · {{ $booking->building->name }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-in</span>
            <span class="booking-value">{{ $booking->check_in->format('D, M j, Y') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Checked Out</span>
            <span class="booking-value">{{ $booking->checked_out_at->format('D, M j, Y · g:i A') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Duration</span>
            <span class="booking-value">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
        </div>
    </div>

    <p class="email-text">
        If you have any feedback about your stay, we'd love to hear from you. We hope to see you again!
    </p>

    <p class="email-text">The {{ config('app.name') }} Team</p>
@endsection
