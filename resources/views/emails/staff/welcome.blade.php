@extends('emails.layout')

@section('content')
    <h1 class="email-title">Welcome to {{ config('app.name') }}!</h1>

    <p class="email-text">Hi {{ $staff->name }},</p>

    <p class="email-text">
        Your staff account has been created. To finish setting up, choose your own
        password using the button below — then you can sign in to the management portal.
    </p>

    <div class="booking-card">
        <div class="booking-reference">Your Account</div>

        <div class="booking-detail">
            <span class="booking-label">Email</span>
            <span class="booking-value">{{ $staff->email }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Role</span>
            <span class="booking-value" style="text-transform: capitalize;">{{ str_replace('-', ' ', $role) }}</span>
        </div>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $resetUrl }}" class="button">Set Your Password</a>
    </div>

    <div class="info-box">
        <p class="info-box-title">🔒 Important</p>
        <p class="info-box-text">
            This link is personal to you and valid for a limited time. If it has expired
            by the time you open it, go to the portal and use "Forgot password" with this
            email address to get a fresh link. Never share your password with anyone.
        </p>
    </div>

    <p class="email-text">
        If you have any questions, please contact your manager or system administrator.
    </p>

    <p class="email-text">
        The {{ config('app.name') }} Team
    </p>
@endsection
