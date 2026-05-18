@extends('emails.layout')

@section('content')
    <h1 class="email-title">Welcome to {{ config('app.name') }}!</h1>

    <p class="email-text">Hi {{ $staff->name }},</p>

    <p class="email-text">
        Your staff account has been created. You can now sign in to the management portal
        using the credentials below.
    </p>

    <div class="booking-card">
        <div class="booking-reference">Your Login Details</div>

        <div class="booking-detail">
            <span class="booking-label">Email</span>
            <span class="booking-value">{{ $staff->email }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Password</span>
            <span class="booking-value">{{ $plainPassword }}</span>
        </div>

        <div class="booking-detail">
            <span class="booking-label">Role</span>
            <span class="booking-value" style="text-transform: capitalize;">{{ str_replace('-', ' ', $role) }}</span>
        </div>
    </div>

    <div class="info-box">
        <p class="info-box-title">🔒 Important</p>
        <p class="info-box-text">
            Please change your password after your first login. Keep your credentials confidential
            and do not share them with anyone.
        </p>
    </div>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ config('app.url') }}/login" class="button">Sign In to Portal</a>
    </div>

    <p class="email-text">
        If you have any questions, please contact your manager or system administrator.
    </p>

    <p class="email-text">
        The {{ config('app.name') }} Team
    </p>
@endsection
