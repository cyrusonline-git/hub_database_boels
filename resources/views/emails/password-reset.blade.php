<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wachtwoord opnieuw instellen</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f7f9; margin:0; padding:24px;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"
           style="background:#ffffff; max-width:560px; width:100%; border-radius:8px; overflow:hidden;">
        <tr>
            <td style="background: {{ $brand['color'] ?? '#FF6600' }}; padding:24px; text-align:center;">
                <div style="display:inline-block; width:44px; height:44px; background:#fff; color: {{ $brand['color'] ?? '#FF6600' }};
                            font-weight:800; font-family:Arial; font-size:24px; line-height:44px; border-radius:6px;">B</div>
                <h1 style="color:#fff; font-size:20px; margin:12px 0 0;">{{ config('boels.brand.name') }} — {{ config('boels.brand.product') }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px;">
                <p>Hallo {{ $user->name }},</p>

                <p>We hebben een verzoek ontvangen om je wachtwoord opnieuw in te stellen.
                Klik op de knop hieronder om een nieuw wachtwoord te kiezen.</p>

                <p style="text-align:center; margin:32px 0;">
                    <a href="{{ $url }}"
                       style="display:inline-block; background: {{ $brand['color'] ?? '#FF6600' }}; color:#ffffff;
                              padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:600;">
                        Nieuw wachtwoord instellen
                    </a>
                </p>

                <p style="font-size:13px; color:#666;">
                    Werkt de knop niet? Kopieer deze link in je browser:<br>
                    <a href="{{ $url }}">{{ $url }}</a>
                </p>

                <p style="font-size:13px; color:#666; margin-top:24px;">
                    Deze link is 60 minuten geldig. Daarna moet je een nieuwe aanvragen via
                    &ldquo;Wachtwoord vergeten?&rdquo; op de inlogpagina.
                </p>

                <p style="font-size:12px; color:#999; margin-top:32px;">
                    Heb jij dit niet aangevraagd? Dan kun je deze mail veilig negeren —
                    je wachtwoord blijft ongewijzigd.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
