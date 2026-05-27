@extends('emails.layout')

@section('content')
    <h1 class="email-title">Emergency Fund Request</h1>

    <p class="email-text">
        {{ $request->requestedBy->name }} has submitted an emergency fund request
        {{ $request->isManagerApproved() ? 'requiring your final approval' : 'requiring your review' }}.
    </p>

    <div class="booking-card">
        <div class="booking-detail">
            <span class="booking-label">Building</span>
            <span class="booking-value">{{ $request->building->name }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Amount Requested</span>
            <span class="booking-value" style="font-size:18px; font-weight:700;">₦{{ number_format($request->amount, 0) }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Reason</span>
            <span class="booking-value">{{ $request->reason }}</span>
        </div>
        <div class="booking-detail" style="display:block; padding: 12px 0;">
            <span class="booking-label">Why It's Urgent</span>
            <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $request->urgency_description }}</p>
        </div>
        @if($request->isManagerApproved() && $request->manager_comment)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">Manager's Comment</span>
                <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $request->manager_comment }}</p>
            </div>
        @endif
    </div>

    <center>
        <a href="{{ route('manage.emergency-fund.show', $request->id) }}" class="button">
            Review Request
        </a>
    </center>
@endsection
