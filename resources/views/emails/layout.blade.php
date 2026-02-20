<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            color: #1f2937;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .email-logo {
            font-size: 28px;
            font-weight: 300;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .email-content {
            padding: 40px 30px;
        }
        .email-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .email-text {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin: 0 0 20px 0;
        }
        .booking-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 30px 0;
        }
        .booking-reference {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        .booking-detail {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .booking-detail:last-child {
            border-bottom: none;
        }
        .booking-label {
            font-size: 14px;
            color: #6b7280;
        }
        .booking-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            text-align: right;
        }
        .total-section {
            background-color: #1f2937;
            color: #ffffff;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .total-row:last-child {
            font-size: 18px;
            font-weight: 600;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #1f2937;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #374151;
        }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin: 0 0 8px 0;
        }
        .info-box-text {
            font-size: 14px;
            color: #1e40af;
            margin: 0;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 10px 0;
        }
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        .footer-links a:hover {
            color: #1f2937;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-header">
        <div class="email-logo">{{ config('app.name') }}</div>
    </div>

    <div class="email-content">
        @yield('content')
    </div>

    <div class="email-footer">
        <p class="footer-text">{{ config('app.name') }} - Premium Apartment Rentals in Abuja</p>
        <div class="footer-links">
            <a href="{{ url('/') }}">Website</a>
            <a href="{{ url('/contact') }}">Contact Us</a>
            <a href="{{ url('/terms') }}">Terms</a>
        </div>
        <p class="footer-text" style="margin-top: 20px;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
</div>
</body>
</html>
