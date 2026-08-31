@php $h = config('hotel'); @endphp
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#f4f5f3;font-family:Arial,Helvetica,sans-serif;color:#374249;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f3;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#374249;padding:22px 28px;color:#fff;">
                <div style="font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:#7e9299;">{{ $h['name'] }}</div>
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Nouveau message de contact</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0;color:#596d7a;width:120px;">Nom</td><td style="padding:6px 0;font-weight:bold;">{{ $contactMessage->name }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">E-mail</td><td style="padding:6px 0;font-weight:bold;"><a href="mailto:{{ $contactMessage->email }}" style="color:#a73514;">{{ $contactMessage->email }}</a></td></tr>
                    @if ($contactMessage->phone)
                        <tr><td style="padding:6px 0;color:#596d7a;">Téléphone</td><td style="padding:6px 0;font-weight:bold;">{{ $contactMessage->phone }}</td></tr>
                    @endif
                    <tr><td style="padding:6px 0;color:#596d7a;">Objet</td><td style="padding:6px 0;font-weight:bold;">{{ $contactMessage->subject }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Reçu le</td><td style="padding:6px 0;font-weight:bold;">{{ $contactMessage->created_at->translatedFormat('D d M Y à H:i') }}</td></tr>
                </table>

                <div style="margin-top:18px;padding:16px 18px;background:#f8f9f7;border-radius:12px;line-height:1.6;white-space:pre-line;">{{ $contactMessage->message }}</div>

                <p style="margin:22px 0 0;font-size:13px;color:#596d7a;">
                    Répondez directement à cet e-mail pour écrire à {{ $contactMessage->name }}.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
