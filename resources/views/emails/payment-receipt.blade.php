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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Reçu de paiement</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <p style="margin:0 0 16px;line-height:1.6;">Bonjour{{ $intent->payer_name ? ' '.$intent->payer_name : '' }},</p>
                <p style="margin:0 0 18px;line-height:1.6;">Nous confirmons la bonne réception de votre paiement. Merci.</p>

                <div style="background:#f8f9f7;border-radius:12px;padding:16px 18px;text-align:center;margin-bottom:20px;">
                    <div style="font-size:12px;color:#596d7a;text-transform:uppercase;letter-spacing:.1em;">Montant réglé</div>
                    <div style="font-size:24px;font-weight:bold;margin-top:4px;">{{ money($intent->amount) }}</div>
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0;color:#596d7a;">Référence</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $intent->reference }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Objet</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $intent->purposeLabel() }}</td></tr>
                    @if ($intent->method)
                        <tr><td style="padding:6px 0;color:#596d7a;">Moyen</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ config('payments.methods.'.$intent->method, $intent->method) }}</td></tr>
                    @endif
                    <tr><td style="padding:6px 0;color:#596d7a;">Date</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ ($intent->paid_at ?? now())->translatedFormat('D d M Y à H:i') }}</td></tr>
                </table>

                <div style="margin-top:22px;text-align:center;">
                    <a href="{{ $receiptUrl }}" style="display:inline-block;background:#de6443;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:999px;">
                        Voir le reçu en ligne
                    </a>
                </div>

                <p style="margin:22px 0 0;font-size:12px;color:#a2aeb4;line-height:1.6;">
                    {{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}<br>
                    Ce message est un accusé de paiement, il ne vaut pas facture.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
