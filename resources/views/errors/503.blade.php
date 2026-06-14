<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We should be back soon - {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            max-width: 480px;
            text-align: center;
        }

        .logo {
            font-size: 22px;
            font-weight: 300;
            letter-spacing: 0.1em;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 48px;
        }

        .logo span {
            color: #f8fafc;
            font-weight: 600;
        }

        .icon {
            width: 64px;
            height: 64px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
        }

        .icon svg {
            width: 28px;
            height: 28px;
            stroke: #64748b;
        }

        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }

        p {
            font-size: 15px;
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 40px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 100px;
            padding: 8px 16px;
            font-size: 13px;
            color: #94a3b8;
        }

        .dot {
            width: 7px;
            height: 7px;
            background: #f59e0b;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .footer {
            margin-top: 48px;
            font-size: 12px;
            color: #334155;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="logo">City<span>Stake</span></div>

    <div class="icon">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" />
        </svg>
    </div>

    <h1>Down for maintenance</h1>
    <p>
        We are making some improvements to the platform.<br>
        We will be back up shortly.
    </p>

    <div class="status">
        <div class="dot"></div>
        Maintenance in progress
    </div>

    <div class="footer">
        {{ config('app.name') }} - Property Management Platform
    </div>

</div>
</body>
</html>
