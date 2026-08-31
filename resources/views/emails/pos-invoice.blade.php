@php $h = config('hotel'); $guest = $order->reservation?->guest; @endphp
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#f4f5f3;font-family:Arial,Helvetica,sans-serif;color:#374249;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f3;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#374249;padding:22px 28px;color:#fff;">
                <div style="font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:#7e9299;">{{ $h['name'] }}</div>
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Facture {{ $order->invoice_number }}</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <p style="margin:0 0 16px;line-height:1.6;">Bonjour{{ $guest ? ' '.$guest->fullName() : '' }},</p>
                <p style="margin:0 0 18px;line-height:1.6;">Veuillez trouver ci-dessous le récapitulatif de votre consommation au {{ $order->venue->name ?? 'restaurant' }}.</p>

                <div style="background:#f8f9f7;border-radius:12px;padding:16px 18px;text-align:center;margin-bottom:20px;">
                    <div style="font-size:12px;color:#596d7a;text-transform:uppercase;letter-spacing:.1em;">Total TTC</div>
                    <div style="font-size:24px;font-weight:bold;margin-top:4px;">{{ money($order->total) }}</div>
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    @foreach ($order->items as $it)
                        <tr>
                            <td style="padding:5px 0;">{{ $it->quantity }}× {{ $it->name }}</td>
                            <td style="padding:5px 0;text-align:right;">{{ money($it->line_total) }}</td>
                        </tr>
                    @endforeach
                    <tr><td style="padding:8px 0 0;border-top:1px solid #e6e8e4;color:#596d7a;">TVA</td><td style="padding:8px 0 0;border-top:1px solid #e6e8e4;text-align:right;">{{ money($order->tax) }}</td></tr>
                </table>

                <div style="margin-top:22px;text-align:center;">
                    <a href="{{ $url }}" style="display:inline-block;background:#de6443;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:999px;">
                        Voir la facture
                    </a>
                </div>

                <p style="margin:22px 0 0;font-size:12px;color:#a2aeb4;line-height:1.6;">
                    {{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}<br>
                    Facture n° {{ $order->invoice_number }} du {{ ($order->invoiced_at ?? now())->format('d/m/Y') }}.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
