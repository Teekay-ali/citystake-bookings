@extends('emails.layout')

@section('content')
    <h1 class="email-title">Guest Message Received</h1>

    <p class="email-text">A guest has sent a message regarding booking <strong>{{ $message->booking->booking_reference }}</strong>.</p>

    <div class="booking-card">
        <div class="booking-detail">
            <span class="booking-label">Guest</span>
            <span class="booking-value">{{ $message->booking->guest_name }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Property</span>
            <span class="booking-value">{{ $message->booking->building->name }}</span>
        </div>
        <div class="booking-detail" style="display:block; padding: 12px 0;">
            <span class="booking-label">Message</span>
            <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $message->body }}</p>
        </div>
    </div>

    <center>
        <a href="{{ route('manage.bookings.show', $message->booking->id) }}" class="button">Reply in Admin Panel</a>
    </center>
@endsection
