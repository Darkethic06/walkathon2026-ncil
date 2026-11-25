<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your bib number: {{ $registration->bib_number }}</title>
</head>
<body style="margin:0;padding:0;background:#dbf6fe;font-family:Arial,Helvetica,sans-serif;color:#1b2b3a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#dbf6fe;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;border-top:6px solid #eba92b;">

                {{-- logo --}}
                <tr>
                    <td align="center" style="padding:26px 24px 8px;">
                        <img src="{{ $message->embed(public_path('images/nephrocare-logo.png')) }}" alt="Nephrocare" width="260" style="display:block;width:260px;max-width:80%;height:auto;">
                    </td>
                </tr>

                {{-- greeting --}}
                <tr>
                    <td align="center" style="padding:10px 28px 0;">
                        <p style="margin:0 0 6px;font-size:13px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#0f6478;">Registration confirmed</p>
                        <h1 style="margin:0 0 10px;font-family:Georgia,serif;font-size:28px;line-height:1.2;color:#0d2c4a;">Thank you, {{ $registration->first_name }}!</h1>
                        <p style="margin:0;font-size:15px;line-height:1.6;color:#33495a;">You're registered for the <strong>Annual Walkathon 2026</strong>. Here is your bib number and QR code. Please keep them handy on the day.</p>
                    </td>
                </tr>

                {{-- bib card --}}
                <tr>
                    <td align="center" style="padding:24px 28px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:2px dashed #1f9db5;border-radius:16px;">
                            <tr>
                                <td align="center" style="padding:20px 16px 6px;">
                                    <p style="margin:0;font-size:12px;font-weight:bold;letter-spacing:3px;text-transform:uppercase;color:#5a6d7c;">Bib number</p>
                                    <p style="margin:4px 0 0;font-size:56px;line-height:1;font-weight:800;color:#0d2c4a;letter-spacing:3px;">{{ $registration->bib_number }}</p>
                                    <p style="margin:8px 0 0;font-size:16px;font-weight:bold;color:#0f6478;">{{ $registration->full_name }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="padding:12px 16px 20px;">
                                    <img src="{{ $message->embedData($qrPng, 'bib-qr.png', 'image/png') }}" alt="QR code for bib {{ $registration->bib_number }}" width="200" height="200" style="display:block;width:200px;height:200px;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- event details --}}
                <tr>
                    <td style="padding:16px 28px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eefbff;border-radius:12px;">
                            <tr><td style="padding:14px 18px 4px;font-size:14px;color:#33495a;"><strong style="color:#0f6478;">Date:</strong> Sunday, 13 December 2026</td></tr>
                            <tr><td style="padding:4px 18px;font-size:14px;color:#33495a;"><strong style="color:#0f6478;">Start &amp; finish:</strong> Salt Lake Stadium, Kolkata</td></tr>
                            <tr><td style="padding:4px 18px 14px;font-size:14px;color:#33495a;"><strong style="color:#0f6478;">Distance:</strong> 4 km walk</td></tr>
                        </table>
                    </td>
                </tr>

                {{-- what next --}}
                <tr>
                    <td align="center" style="padding:20px 28px 8px;">
                        <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#33495a;">Show this QR code at the bib collection desk. Reporting time and bib collection details will be shared by email and WhatsApp closer to the event.</p>
                        <a href="{{ $thankYouUrl }}" style="display:inline-block;background:#e8971a;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 28px;border-radius:50px;">View &amp; download your bib</a>
                    </td>
                </tr>

                {{-- footer --}}
                <tr>
                    <td align="center" style="padding:26px 24px;background:#0b3b4d;color:#d6e8ee;font-size:12px;line-height:1.6;">
                        <strong style="color:#ffffff;">NephroCare</strong> in association with <strong style="color:#ffffff;">Vivacity Multispeciality Hospital</strong><br>
                        Annual Walkathon 2026 &nbsp;|&nbsp; Walk for Kidney, Walk for Health
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
