<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Scan Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6fb;color:#1f2937;font-family:Arial, sans-serif;">
    @php
        $schedule = $attendanceSession->classSchedule;
        $course = optional($schedule->course)->code ?: $schedule->subject;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($scanUrl);
    @endphp

    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f6fb;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 18px 50px rgba(15, 23, 42, 0.08);">
                    <tr>
                        <td style="padding:24px 32px;background:#0f172a;color:#ffffff;">
                            <h1 style="margin:0;font-size:24px;font-weight:700;">Attendance Scan Code</h1>
                            <p style="margin:8px 0 0;font-size:14px;color:#cbd5e1;">Secure your attendance with this scan code.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 24px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hello <strong>{{ $student->name }}</strong>,</p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.7;color:#475569;">Your attendance scan code is ready for this class. Scan the QR code below with your phone camera, or click the button to register attendance.</p>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                                        <p style="margin:0 0 10px;font-size:14px;color:#0f172a;font-weight:700;">Class details</p>
                                        <p style="margin:6px 0;font-size:14px;color:#475569;"><strong>Course:</strong> {{ $course }} {{ optional($schedule->course)->title }}</p>
                                        <p style="margin:6px 0;font-size:14px;color:#475569;"><strong>Date:</strong> {{ $attendanceSession->attendance_date->format('M d, Y') }}</p>
                                        <p style="margin:6px 0;font-size:14px;color:#475569;"><strong>Time:</strong> {{ $schedule->start_time }} - {{ $schedule->end_time }}</p>
                                        <p style="margin:6px 0;font-size:14px;color:#475569;"><strong>Room:</strong> {{ $schedule->room }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin-bottom:24px;">
                                <tr>
                                    <td align="center" style="padding:16px;background:#f8fafc;border-radius:16px;">
                                        <img src="{{ $qrUrl }}" alt="Attendance scan code" width="220" height="220" style="border-radius:16px;border:1px solid #e2e8f0;" />
                                    </td>
                                </tr>
                            </table>

                            <table cellpadding="0" cellspacing="0" role="presentation" style="width:100%;margin-bottom:16px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $scanUrl }}" style="display:inline-block;padding:14px 22px;background:#0f172a;color:#ffffff;text-decoration:none;border-radius:10px;font-size:16px;font-weight:600;">Register Attendance</a>
                                    </td>
                                </tr>
                            </table>

                            @if($attendanceSession->scan_expires_at)
                                <p style="margin:0;font-size:14px;color:#64748b;">This scan code expires at <strong>{{ $attendanceSession->scan_expires_at->format('M d, Y g:i A') }}</strong>.</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;color:#64748b;font-size:13px;">
                            <p style="margin:0;">If you are not logged in, you will be asked to login first before registering attendance.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 24px;background:#f8fafc;color:#94a3b8;font-size:12px;text-align:center;">
                            <p style="margin:0;">Powered by Educore | Please contact your lecturer if you have any issues accessing the scan code.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
