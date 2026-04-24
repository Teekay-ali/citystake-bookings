@extends('emails.layout')

@section('content')
    <h1 class="email-title">New Message from {{ config('app.name') }}</h1>

    <p class="email-text">Hi {{ $message->booking->guest_name }},</p>

    <p class="email-text">You have a new message regarding your booking:</p>

    <div class="booking-card">
        <div class="booking-reference">{{ $message->booking->booking_reference }}</div>
        <div class="booking-detail">
            <span class="booking-label">From</span>
            <span class="booking-value">{{ $message->sender->name ?? config('app.name') }} (Property Team)</span>
        </div>
        <div class="booking-detail" style="display:block; padding: 12px 0;">
            <span class="booking-label">Message</span>
            <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $message->body }}</p>
        </div>
    </div>

    <center>
        <a href="{{ route('bookings.show', $message->booking->id) }}" class="button">View & Reply</a>
    </center>

    <p class="email-text">
        The {{ config('app.name') }} Team
    </p>
@endsection
