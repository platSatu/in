{{-- Email notifikasi dokumen student (App\Mail\DocumentNotificationMail). Gaya inline supaya rapi di semua aplikasi email. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px; background:#ffffff; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="background:#C8102E; padding:20px 28px; color:#ffffff; font-size:18px; font-weight:700;">INA Study</td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            @foreach ($lines as $line)
                                <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">{!! nl2br(e($line)) !!}</p>
                            @endforeach

                            @if ($buttonUrl)
                                <p style="margin:24px 0 8px;">
                                    <a href="{{ $buttonUrl }}" style="display:inline-block; background:#C8102E; color:#ffffff; text-decoration:none; font-weight:600; padding:12px 24px; border-radius:8px;">
                                        {{ $buttonLabel ?? 'Buka' }}
                                    </a>
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background:#f9fafb; color:#9ca3af; font-size:12px;">
                            Email ini dikirim otomatis oleh sistem INA Study.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
