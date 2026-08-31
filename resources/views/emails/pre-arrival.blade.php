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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Votre arrivée approche</div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.6;">
                <p style="margin:0 0 14px;">Bonjour {{ $reservation->guest->first_name }},</p>
                <p style="margin:0 0 16px;">
                    Nous avons hâte de vous accueillir le <strong>{{ $reservation->check_in->translatedFormat('l d F Y') }}</strong>
                    en {{ $reservation->roomCategory->name }} (réf. {{ $reservation->reference }}).
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:5px 0;color:#596d7a;">Arrivée</td><td style="padding:5px 0;text-align:right;font-weight:bold;">à partir de {{ $h['checkin_time'] ?? '14:00' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#596d7a;">Départ</td><td style="padding:5px 0;text-align:right;font-weight:bold;">avant {{ $h['checkout_time'] ?? '12:00' }}</td></tr>
                    <tr><td style="padding:5px 0;color:#596d7a;">Adresse</td><td style="padding:5px 0;text-align:right;font-weight:bold;">{{ $h['contact']['address_line'] ?? '' }}, {{ $h['city'] ?? '' }}</td></tr>
                </table>

                @if ($appUrl)
                    <div style="margin-top:20px;text-align:center;">
                        <a href="{{ $appUrl }}" style="display:inline-block;background:#de6443;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:999px;">
                            Préparer mon arrivée
                        </a>
                        <div style="font-size:12px;color:#a2aeb4;margin-top:8px;">Folio, demandes, room service, fidélité — sans créer de compte.</div>
                    </div>
                @endif

                <p style="margin:18px 0 0;">Un déplacement, une heure d’arrivée tardive ? Répondez à cet e-mail, nous nous adaptons.</p>
                <p style="margin:18px 0 0;font-size:12px;color:#a2aeb4;">{{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
