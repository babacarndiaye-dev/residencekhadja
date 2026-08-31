@php $h = config('hotel'); @endphp
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#f4f5f3;font-family:Arial,Helvetica,sans-serif;color:#374249;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f3;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#374249;padding:22px 28px;color:#fff;">
                <div style="font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:#7e9299;">{{ $h['name'] }}</div>
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Devis {{ $quote->reference }}</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <p style="margin:0 0 8px;line-height:1.6;">Bonjour{{ $quote->lead?->contact_name ? ' '.$quote->lead->contact_name : '' }},</p>
                <p style="margin:0 0 18px;line-height:1.6;">
                    Veuillez trouver ci-dessous notre proposition pour <strong>{{ $quote->title }}</strong>
                    ({{ $quote->pax }} personnes).
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
                    <tr style="color:#596d7a;text-transform:uppercase;letter-spacing:.06em;font-size:11px;">
                        <td style="padding:8px 0;border-bottom:1px solid #e3e7ef;">Prestation</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e3e7ef;text-align:center;">Qté</td>
                        <td style="padding:8px 0;border-bottom:1px solid #e3e7ef;text-align:right;">Total</td>
                    </tr>
                    @foreach ($quote->items as $item)
                        <tr>
                            <td style="padding:8px 0;border-bottom:1px solid #f0f2f7;">{{ $item->label }}</td>
                            <td style="padding:8px 0;border-bottom:1px solid #f0f2f7;text-align:center;">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td style="padding:8px 0;border-bottom:1px solid #f0f2f7;text-align:right;">{{ money($item->total) }}</td>
                        </tr>
                    @endforeach
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin-top:14px;">
                    <tr><td style="padding:4px 0;color:#596d7a;">Sous-total</td><td style="padding:4px 0;text-align:right;">{{ money($quote->subtotal) }}</td></tr>
                    @if ($quote->discount_amount)
                        <tr><td style="padding:4px 0;color:#596d7a;">Remise</td><td style="padding:4px 0;text-align:right;">− {{ money($quote->discount_amount) }}</td></tr>
                    @endif
                    <tr><td style="padding:4px 0;color:#596d7a;">TVA ({{ rtrim(rtrim(number_format($quote->tax_rate * 100, 2), '0'), '.') }} %)</td><td style="padding:4px 0;text-align:right;">{{ money($quote->tax_amount) }}</td></tr>
                    <tr><td style="padding:10px 0;border-top:1px solid #e3e7ef;font-weight:bold;">Total</td><td style="padding:10px 0;border-top:1px solid #e3e7ef;text-align:right;font-weight:bold;font-size:16px;">{{ money($quote->total) }}</td></tr>
                    <tr><td style="padding:4px 0;color:#596d7a;">Acompte pour réserver la date</td><td style="padding:4px 0;text-align:right;font-weight:bold;">{{ money($quote->deposit_amount) }}</td></tr>
                </table>

                @if ($quote->valid_until)
                    <p style="margin:18px 0 0;font-size:13px;color:#596d7a;">Offre valable jusqu’au {{ $quote->valid_until->translatedFormat('d F Y') }}.</p>
                @endif

                <p style="margin:16px 0 0;line-height:1.6;">
                    Pour toute question ou pour confirmer, répondez simplement à cet e-mail — l’équipe commerciale
                    de l’{{ $h['name'] }} reste à votre disposition.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
