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
            </td></tr>

            <tr><td style="padding:26px 28px;font-size:15px;line-height:1.7;white-space:pre-line;">{{ $bodyText }}</td></tr>

            @if ($campaign->promoCode)
                <tr><td style="padding:0 28px 26px;">
                    <div style="background:#f8f9f7;border:1px dashed #7e9299;border-radius:12px;padding:14px 18px;text-align:center;">
                        <div style="font-size:12px;color:#596d7a;text-transform:uppercase;letter-spacing:.1em;">Votre code</div>
                        <div style="font-size:20px;font-weight:bold;letter-spacing:.12em;margin-top:4px;">{{ $campaign->promoCode->code }}</div>
                    </div>
                </td></tr>
            @endif

            <tr><td style="padding:0 28px 26px;font-size:12px;color:#a2aeb4;line-height:1.6;border-top:1px solid #f4f5f3;padding-top:16px;">
                Vous recevez cet e-mail car vous avez accepté les communications de l’{{ $h['name'] }}.
                Pour ne plus en recevoir, répondez « STOP » à ce message.
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
