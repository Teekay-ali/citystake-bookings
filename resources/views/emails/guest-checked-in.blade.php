@extends('emails.layout')

@section('content')
    <h1 class="email-title">You're Checked In! 🏠</h1>

    <p class="email-text">Dear {{ $booking->guest_name }},</p>

    <p class="email-text">
        Welcome! Your check-in has been confirmed by our team. We hope you enjoy your stay.
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
            <span class="booking-label">Unit</span>
            <span class="booking-value">Unit {{ $booking->unit->unit_number }}{{ $booking->unit->floor ? ' · ' . $booking->unit->floor . ' Floor' : '' }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Checked In</span>
            <span class="booking-value">{{ $booking->checked_in_at->format('D, M j, Y · g:i A') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-out</span>
            <span class="booking-value">{{ $booking->check_out->format('D, M j, Y') }} (Before 12:00 PM)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Duration</span>
            <span class="booking-value">{{ $booking->nights }} night{{ $booking->nights > 1 ? 's' : '' }}</span>
        </div>
    </div>

    <p class="email-text">
        If you need anything during your stay, please don't hesitate to reach out to our team.
    </p>

    <p class="email-text">The {{ config('app.name') }} Team</p>
@endsection
