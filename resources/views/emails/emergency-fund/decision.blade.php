@extends('emails.layout')

@section('content')
    <h1 class="email-title">
        Emergency Fund Request
        @if($request->isManagerApproved())
            Approved by Manager
        @elseif($request->isApproved())
            Approved
        @else
            Declined
        @endif
    </h1>

    <p class="email-text">
        Your emergency fund request has been
        <strong>{{ $request->isDeclined() ? 'declined' : 'approved' }}</strong>
        by
        @if($request->isManagerApproved())
            {{ $request->managerApprovedBy->name }} (Manager). It has been forwarded to the CEO for final approval.
        @else
            {{ $request->approvedBy->name }}.
        @endif
    </p>

    <div class="booking-card">
        <div class="booking-detail">
            <span class="booking-label">Building</span>
            <span class="booking-value">{{ $request->building->name }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Amount</span>
            <span class="booking-value">₦{{ number_format($request->amount, 0) }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Reason</span>
            <span class="booking-value">{{ $request->reason }}</span>
        </div>
        @if($request->manager_comment)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">Manager's Comment</span>
                <p style="margin-top:8px; font-size:14px; color:#374151;">{{ $request->manager_comment }}</p>
            </div>
        @endif
        @if($request->ceo_comment)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">CEO's Comment</span>
                <p style="margin-top:8px; font-size:14px; color:#374151;">{{ $request->ceo_comment }}</p>
            </div>
        @endif
    </div>

    @if($request->isApproved())
        <p class="email-text">You may now proceed with the payment and mark it as paid in the system.</p>
    @endif

    <center>
        <a href="{{ route('manage.emergency-fund.show', $request->id) }}" class="button">
            View Request
        </a>
    </center>
@endsection
