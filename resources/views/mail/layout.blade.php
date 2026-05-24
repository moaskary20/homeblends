<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="font-family: Tahoma, Arial, sans-serif; background:#f9fafb; padding:24px; direction:rtl;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="background:#d97706;color:#fff;padding:20px;text-align:center;font-size:20px;font-weight:bold;">
                {{ config('app.name') }}
            </td>
        </tr>
        <tr>
            <td style="padding:24px;color:#111827;line-height:1.6;">
                {{ $slot }}
            </td>
        </tr>
        <tr>
            <td style="padding:16px;background:#f3f4f6;color:#6b7280;font-size:12px;text-align:center;">
                © {{ date('Y') }} {{ config('app.name') }}
            </td>
        </tr>
    </table>
</body>
</html>
