@php $h = config('hotel'); @endphp
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#f4f5f3;font-family:Arial,Helvetica,sans-serif;color:#374249;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f3;padding:24px 0;">
    <tr><td align="center">
        <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;">
            <tr><td style="background:#374249;padding:22px 28px;color:#fff;">
                <div style="font-size:13px;letter-spacing:.16em;text-transform:uppercase;color:#7e9299;">{{ $h['name'] }} · Décisionnel</div>
                <div style="font-size:19px;font-weight:bold;margin-top:4px;">Rapport planifié</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <p style="margin:0 0 14px;line-height:1.6;">Le rapport <strong>{{ $label }}</strong> est prêt.</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0;color:#596d7a;">Période</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $periodStart }} → {{ $periodEnd }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Lignes</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ number_format($rowCount, 0, ',', ' ') }}</td></tr>
                </table>

                <p style="margin:18px 0 0;font-size:13px;color:#596d7a;line-height:1.6;">
                    Les données complètes sont dans le fichier CSV joint (<strong>{{ $filename }}</strong>),
                    ouvrable dans un tableur.
                </p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
