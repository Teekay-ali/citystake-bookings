<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:12px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1f2937; padding:32px 30px; text-align:center;">
                            <span style="font-size:26px; font-weight:300; color:#ffffff; letter-spacing:-0.5px;">{{ config('app.name') }}</span>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:36px 30px;">
                            <h1 style="margin:0 0 20px 0; font-size:22px; font-weight:600; color:#1f2937;">
                                Platform Update{{ $changelog->version ? ' · ' . $changelog->version : '' }}
                            </h1>

                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6; color:#4b5563;">Hi {{ $notifiable->name }},</p>
                            <p style="margin:0 0 24px 0; font-size:16px; line-height:1.6; color:#4b5563;">
                                A new update has been published on {{ config('app.name') }}.
                            </p>

                            <!-- Card -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:12px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <p style="margin:0 0 6px 0; font-size:13px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px;">
                                            {{ $changelog->type }}{{ $changelog->version ? ' · ' . $changelog->version : '' }}
                                        </p>
                                        <p style="margin:0 0 18px 0; font-size:18px; font-weight:600; color:#1f2937;">{{ $changelog->title }}</p>

                                        <div style="font-size:15px; line-height:1.6; color:#374151;">
                                            {!! $changelog->body !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                                <tr>
                                    <td style="background-color:#1f2937; border-radius:8px;">
                                        <a href="{{ config('app.url') }}/manage/changelogs"
                                           style="display:inline-block; padding:13px 30px; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none;">
                                            View all updates
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:16px; line-height:1.6; color:#4b5563;">The {{ config('app.name') }} Team</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb; padding:28px 30px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280;">{{ config('app.name') }} - Premium Apartment Rentals in Abuja</p>
                            <p style="margin:0; font-size:12px; color:#9ca3af;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
