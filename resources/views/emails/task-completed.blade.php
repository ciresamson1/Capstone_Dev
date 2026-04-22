<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Completed</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:640px;margin:40px auto;padding:0 16px;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">

            {{-- Branded Header --}}
            <div style="background:#0f172a;padding:28px 32px;">
                <table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:16px;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="display:inline-block;background:#4e74fb;color:#ffffff;font-weight:800;font-size:16px;padding:8px 14px;border-radius:8px;letter-spacing:0.05em;">PCMS</div>
                                    </td>
                                    <td style="vertical-align:middle;padding-left:14px;">
                                        <div style="font-size:17px;font-weight:700;color:#ffffff;line-height:1.2;">SGpro.co</div>
                                        <div style="font-size:11px;color:#94a3b8;letter-spacing:0.06em;text-transform:uppercase;margin-top:2px;">Project Coordination &amp; Management</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <h2 style="margin:0;font-size:20px;font-weight:700;color:#ffffff;">Task Completed ✅</h2>
            </div>

            <div style="padding:26px 32px;">

            <p style="margin:0 0 12px;color:#334155;font-size:14px;line-height:1.7;">
                Your task <strong>{{ $task->title }}</strong> has been marked as completed.
            </p>

            <p style="margin:0 0 12px;color:#334155;font-size:14px;line-height:1.7;">
                Any modifications will be considered as a new job order.
            </p>

            <p style="margin:0 0 20px;color:#334155;font-size:14px;line-height:1.7;">
                Please contact your Project Manager
                @if($pmEmail)
                    (<a href="mailto:{{ $pmEmail }}" style="color:#2563eb;text-decoration:none;">{{ $pmName }}</a>)
                @else
                    ({{ $pmName }})
                @endif
                for your concern or revisions.
            </p>

            <p style="text-align:center;margin:0 0 10px;">
                <a href="{{ $taskUrl }}" style="display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;padding:12px 22px;border-radius:999px;font-size:14px;font-weight:700;">View Task</a>
            </p>

            <p style="margin:0;color:#94a3b8;font-size:12px;text-align:center;">
                If the button does not work, copy this link: <a href="{{ $taskUrl }}" style="color:#2563eb;word-break:break-all;">{{ $taskUrl }}</a>
            </p>
            </div>

            {{-- Footer --}}
            <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 32px;text-align:center;">
                <p style="margin:0;font-size:11px;color:#94a3b8;">SGpro.co &mdash; Project Coordination &amp; Management System. Do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
