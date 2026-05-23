@extends('emails.layout')

@section('content')
    <h1 class="email-title">
        Payment Request {{ $approval->isApproved() ? 'Approved' : 'Declined' }}
    </h1>

    <p class="email-text">
        Your payment request has been <strong>{{ $approval->isApproved() ? 'approved' : 'declined' }}</strong>
        by {{ $approval->approvedBy->name }}.
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
            <span class="booking-label">Decision</span>
            <span class="booking-value" style="color: {{ $approval->isApproved() ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                {{ $approval->isApproved() ? 'Approved ✓' : 'Declined ✗' }}
            </span>
        </div>
        @if($approval->ceo_comment)
            <div class="booking-detail" style="display:block; padding: 12px 0;">
                <span class="booking-label">Comment from {{ $approval->approvedBy->name }}</span>
                <p style="margin-top:8px; font-size:14px; color:#374151; line-height:1.6;">{{ $approval->ceo_comment }}</p>
            </div>
        @endif
    </div>

    @if($approval->isApproved())
        <p class="email-text">
            You may now proceed with the payment and mark it as paid in the system.
        </p>
    @endif

    <center>
        <a href="{{ route('manage.payment-approvals.show', $approval->id) }}" class="button">
            View Request
        </a>
    </center>
@endsection
