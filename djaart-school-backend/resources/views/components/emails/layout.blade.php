<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; max-width:560px; width:100%;">
                    <tr>
                        <td style="background-color:#0f2a4a; padding:20px 28px;">
                            <span style="color:#ffffff; font-size:18px; font-weight:bold; letter-spacing:0.5px;">DJAART SCHOOL</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background-color:#f8fafc; border-top:1px solid #e2e8f0;">
                            <span style="font-size:12px; color:#64748b;">
                                Ceci est un e-mail automatique de la plateforme DJAART SCHOOL — merci de ne pas y répondre directement.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
