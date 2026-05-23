@extends('emails.layout')

@section('content')
    <h1 class="email-title">Payment Approval Request</h1>

    <p class="email-text">
        {{ $approval->requestedBy->name }} has submitted a payment request requiring your approval.
    </p>

    <div class="booking-card">
        <div class="booking-detail">
            <span class="booking-label">Type</span>
            <span class="booking-value">{{ $approval->type_label }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Recipient</span>
            <span class="booking-value">{{ $approval->recipient_name }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Amount</span>
            <span class="booking-value">₦{{ number_format($approval->amount, 0) }}</span>
        </div>
        <div class="booking-detail">
            <span class="booking-label">Building</span>
            <span class="booking-value">{{ $approval->building->name }}</span>
        </div>
        @if($approval->description)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">Description</span>
                <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $approval->description }}</p>
            </div>
        @endif
    </div>

    <center>
        <a href="{{ route('manage.payment-approvals.show', $approval->id) }}" class="button">
            Review Request
        </a>
    </center>
@endsection
