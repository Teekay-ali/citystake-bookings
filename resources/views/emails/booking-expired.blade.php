@extends('emails.layout')

@section('content')
    <h1 class="email-title">Booking Request Expired</h1>

    <p class="email-text">
        Dear {{ $booking->guest_name }},
    </p>

    <p class="email-text">
        Your booking request for <strong>{{ $booking->unitType->name }}</strong> at
        <strong>{{ $booking->building->name }}</strong> has expired because payment was
        not completed within 30 minutes.
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
    </div>

    <p class="email-text">
        No payment was taken. If you'd still like to book, you're welcome to start a new reservation —
        subject to availability.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ config('app.url') }}/properties" class="email-button">
            Browse Available Properties
        </a>
    </div>

    <p class="email-text">
        If you believe this is an error or need assistance, please contact us and we'll be happy to help.
    </p>
@endsection
