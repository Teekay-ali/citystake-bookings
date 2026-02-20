@extends('emails.layout')

@section('content')
    <h1 class="email-title">Booking Cancelled</h1>

    <p class="email-text">
        Dear {{ $booking->guest_name }},
    </p>

    <p class="email-text">
        Your booking has been cancelled as requested. We're sorry to see you go, but we hope to host you in the future.
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
            <span class="booking-label">Check-in Date</span>
            <span class="booking-value">{{ $booking->check_in->format('D, M j, Y') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Check-out Date</span>
            <span class="booking-value">{{ $booking->check_out->format('D, M j, Y') }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Status</span>
            <span class="booking-value" style="color: #dc2626;">Cancelled</span>
        </div>
    </div>

    @if($booking->payment_status === 'paid')
        <div class="info-box">
            <p class="info-box-title">💳 Refund Information</p>
            <p class="info-box-text">
                If you paid for this booking, a refund will be processed to your original payment method within 5-7 business days.
            </p>
        </div>
    @endif

    <p class="email-text">
        If you cancelled by mistake or have any questions, please contact us immediately and we'll do our best to help.
    </p>

    <center>
        <a href="{{ url('/') }}" class="button">Browse Available Properties</a>
    </center>

    <p class="email-text">
        Thank you for considering {{ config('app.name') }}.<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
