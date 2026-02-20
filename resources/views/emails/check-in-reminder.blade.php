@extends('emails.layout')

@section('content')
    <h1 class="email-title">Check-in Tomorrow! 🏠</h1>

    <p class="email-text">
        Dear {{ $booking->guest_name }},
    </p>

    <p class="email-text">
        This is a friendly reminder that your check-in is tomorrow! We're looking forward to welcoming you.
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
            <span class="booking-label">Check-in</span>
            <span class="booking-value">{{ $booking->check_in->format('D, M j, Y') }} (After 2:00 PM)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-out</span>
            <span class="booking-value">{{ $booking->check_out->format('D, M j, Y') }} (Before 12:00 PM)</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Unit</span>
            <span class="booking-value">Unit {{ $booking->unit->unit_number }} ({{ $booking->unit->floor }} Floor)</span>
        </div>
    </div>

    <div class="info-box">
        <p class="info-box-title">📍 Check-in Location</p>
        <p class="info-box-text">
            {{ $booking->building->name }}<br>
            {{ $booking->building->address }}, {{ $booking->building->city }}
        </p>
    </div>

    <div class="info-box" style="background-color: #dcfce7; border-color: #22c55e;">
        <p class="info-box-title" style="color: #166534;">⏰ Check-in Instructions</p>
        <p class="info-box-text" style="color: #166534;">
            • Check-in time: After 2:00 PM<br>
            • Please bring a valid ID<br>
            • Contact our reception for any assistance<br>
            • Early check-in may be available upon request
        </p>
    </div>

    <center>
        <a href="{{ route('bookings.show', $booking->id) }}" class="button">View Full Details</a>
    </center>

    <p class="email-text">
        If you have any questions or need to make changes, please contact us as soon as possible.
    </p>

    <p class="email-text">
        Safe travels and see you soon!<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
