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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Merci de votre séjour</div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.6;">
                <p style="margin:0 0 14px;">Bonjour {{ $reservation->guest->first_name }},</p>
                <p style="margin:0 0 16px;">
                    Nous espérons que votre séjour à l’{{ $h['name'] }} vous a pleinement satisfait.
                    Votre départ est enregistré.
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;background:#f8f9f7;border-radius:12px;">
                    <tr><td style="padding:12px 16px 6px;color:#596d7a;">Référence</td><td style="padding:12px 16px 6px;text-align:right;font-weight:bold;">{{ $reservation->reference }}</td></tr>
                    <tr><td style="padding:6px 16px;color:#596d7a;">Facture n°</td><td style="padding:6px 16px;text-align:right;font-weight:bold;">{{ $reservation->invoice_number }}</td></tr>
                    <tr><td style="padding:6px 16px;color:#596d7a;">Total séjour</td><td style="padding:6px 16px;text-align:right;font-weight:bold;">{{ money($reservation->total) }}</td></tr>
                    <tr><td style="padding:6px 16px 12px;color:#596d7a;">Solde</td><td style="padding:6px 16px 12px;text-align:right;font-weight:bold;">{{ money($reservation->balance()) }}</td></tr>
                </table>

                <p style="margin:16px 0 0;">
                    Une facture détaillée peut vous être remise à la réception ou envoyée sur simple demande.
                    Nous serions ravis de vous accueillir à nouveau.
                </p>
                <p style="margin:18px 0 0;font-size:12px;color:#a2aeb4;">{{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
