@extends('emails.layout')

@section('content')
    <h1 class="email-title">
        Platform Update{{ $changelog->version ? ' · ' . $changelog->version : '' }}
    </h1>

    <p class="email-text">Hi {{ $notifiable->name }},</p>

    <p class="email-text">A new update has been published on {{ config('app.name') }}.</p>

    <div class="booking-card">
        <div class="booking-reference">{{ $changelog->title }}</div>

        <div class="booking-detail">
            <span class="booking-label">Type</span>
            <span class="booking-value" style="text-transform: capitalize;">{{ $changelog->type }}</span>
        </div>

        @if($changelog->version)
            <div class="booking-detail">
                <span class="booking-label">Version</span>
                <span class="booking-value">{{ $changelog->version }}</span>
            </div>
        @endif

        <div class="booking-detail" style="display:block; padding: 12px 0;">
            <span class="booking-label">Details</span>
            <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6; white-space: pre-line;">{{ $changelog->body }}</p>
        </div>
    </div>

    <center>
        <a href="{{ config('app.url') }}/manage/changelogs" class="button">View All Updates</a>
    </center>

    <p class="email-text">The {{ config('app.name') }} Team</p>
@endsection
