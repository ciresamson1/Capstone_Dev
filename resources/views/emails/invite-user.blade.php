<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>You're invited to PCMS</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="max-width:600px;margin:40px auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

        {{-- Branded Header --}}
        <div style="background:#0f172a;padding:28px 36px;">
            <table cellpadding="0" cellspacing="0" style="width:100%;">
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
            <h1 style="margin:20px 0 0;font-size:20px;font-weight:700;color:#ffffff;">You've been invited to join PCMS</h1>
        </div>

        <div style="padding:32px 36px;">
        <p>Hello,</p>
        <p>You have been invited to create your account with the role of <strong>{{ strtoupper($role) }}</strong>.</p>
        <p>Click the button below to complete your registration and set up your profile.</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $inviteUrl }}" style="background: #0f766e; color: #fff; padding: 14px 24px; text-decoration: none; border-radius: 10px; display: inline-block;">Complete Registration</a>
        </p>
        <p>If the button does not work, copy and paste the following link into your browser:</p>
        <p><a href="{{ $inviteUrl }}">{{ $inviteUrl }}</a></p>
        <p>Thanks,<br><strong>PCMS Team</strong> at <a href="https://sgpro.co" style="color:#4e74fb;text-decoration:none;">SGpro.co</a></p>
        </div>

        {{-- Footer --}}
        <div style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 36px;text-align:center;">
            <p style="margin:0;font-size:11px;color:#94a3b8;">SGpro.co &mdash; Project Coordination &amp; Management System. Do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
