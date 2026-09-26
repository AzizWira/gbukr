<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7ff;font-family:Arial,Helvetica,sans-serif;color:#20304f;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f7ff;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #e0e6f4;border-radius:24px;overflow:hidden;box-shadow:0 14px 40px rgba(44,67,120,.08);">
                <tr>
                    <td style="padding:24px 28px;background:#f8f5ff;border-bottom:1px solid #edf0f8;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td width="126" valign="middle">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="padding-right:8px;"><img src="{{ $message->embed(public_path('images/gbukpop.jpeg')) }}" alt="GBUKPOP" width="52" style="display:block;width:52px;height:52px;object-fit:contain;border-radius:10px;"></td>
                                            <td><img src="{{ $message->embed(public_path('images/krjastip.jpeg')) }}" alt="KRJASTIP" width="52" style="display:block;width:52px;height:52px;object-fit:contain;border-radius:10px;"></td>
                                        </tr>
                                    </table>
                                </td>
                                <td valign="middle">
                                    <div style="font-size:20px;font-weight:800;letter-spacing:2px;color:#2d63d7;">GBUKR</div>
                                    <div style="font-size:12px;font-weight:700;color:#7a8296;margin-top:3px;">GBUKPOP x KRJASTIP</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 30px 24px;">
                        <div style="font-size:13px;font-weight:800;color:#2d63d7;letter-spacing:1.3px;text-transform:uppercase;margin-bottom:10px;">GBUKPOP x KRJASTIP</div>
                        <h1 style="font-size:26px;line-height:1.25;margin:0 0 16px;color:#20304f;">{{ $headline }}</h1>
                        <p style="font-size:16px;line-height:1.7;margin:0 0 18px;color:#58647c;">Halo {{ $recipientName }},</p>
                        @foreach($lines as $line)
                            <p style="font-size:15px;line-height:1.75;margin:0 0 14px;color:#58647c;">{{ $line }}</p>
                        @endforeach
                        @if($actionText && $actionUrl)
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 8px;">
                                <tr>
                                    <td bgcolor="#2d63d7" style="border-radius:14px;">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 20px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;">{{ $actionText }}</a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                        @if($footnote)
                            <div style="margin-top:24px;padding:14px 16px;border-radius:14px;background:#fff6fb;border:1px solid #f2ddeb;font-size:13px;line-height:1.65;color:#6e6070;">{{ $footnote }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 30px;background:#f7fbff;border-top:1px solid #edf0f8;font-size:12px;line-height:1.6;color:#7f889a;">
                        Email ini dikirim otomatis oleh <strong style="color:#59657d;">GBUKPOP x KRJASTIP</strong>. Jangan membalas email ini jika tidak diperlukan.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
