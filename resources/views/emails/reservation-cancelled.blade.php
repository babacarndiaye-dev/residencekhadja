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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Réservation annulée</div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.6;">
                <p style="margin:0 0 14px;">Bonjour {{ $reservation->guest->first_name }},</p>
                <p style="margin:0 0 16px;">
                    Votre réservation <strong>{{ $reservation->reference }}</strong>
                    ({{ $reservation->roomCategory->name }}, du {{ $reservation->check_in->translatedFormat('d M Y') }}
                    au {{ $reservation->check_out->translatedFormat('d M Y') }}) a bien été annulée.
                </p>
                <p style="margin:0 0 16px;">
                    Si un acompte a été réglé, notre équipe revient vers vous concernant son remboursement
                    selon les conditions de votre tarif.
                </p>
                <p style="margin:0;">Au plaisir de vous accueillir à une prochaine occasion.</p>
                <p style="margin:18px 0 0;font-size:12px;color:#a2aeb4;">{{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
