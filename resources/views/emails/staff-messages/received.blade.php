@extends('emails.layout')

@section('content')
    <h1 class="email-title">
        {{ $isReply ? 'New Reply from ' . $staffMessage->sender->name : 'New Message from ' . $staffMessage->sender->name }}
    </h1>

    <p class="email-text">Hi {{ $notifiable->name }},</p>

    <p class="email-text">
        {{ $isReply ? 'You have a new reply to your message.' : 'You have a new internal message.' }}
    </p>

    <div class="booking-card">
        @if($staffMessage->subject)
            <div class="booking-detail">
                <span class="booking-label">Subject</span>
                <span class="booking-value">{{ $staffMessage->subject }}</span>
            </div>
        @endif
        <div class="booking-detail">
            <span class="booking-label">From</span>
            <span class="booking-value">{{ $staffMessage->sender->name }}</span>
        </div>
        <div class="booking-detail" style="display:block; padding: 12px 0;">
            <span class="booking-label">Message</span>
            <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6; white-space:pre-line;">{{ $staffMessage->body }}</p>
        </div>
    </div>

    <center>
        <a href="{{ config('app.url') }}/manage/staff-messages" class="button">View & Reply</a>
    </center>

    <p class="email-text">The {{ config('app.name') }} Team</p>
@endsection
