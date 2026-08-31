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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">Réservation confirmée</div>
            </td></tr>

            <tr><td style="padding:26px 28px;">
                <p style="margin:0 0 14px;">Bonjour {{ $reservation->guest->first_name }},</p>
                <p style="margin:0 0 18px;line-height:1.6;">
                    Nous avons le plaisir de confirmer votre réservation à l’{{ $h['name'] }}.
                    Votre référence est&nbsp;:
                </p>

                <div style="background:#f8f9f7;border-radius:12px;padding:16px 18px;text-align:center;margin-bottom:20px;">
                    <span style="font-size:22px;font-weight:bold;letter-spacing:.08em;">{{ $reservation->reference }}</span>
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                    <tr><td style="padding:6px 0;color:#596d7a;">Arrivée</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $reservation->check_in->translatedFormat('D d M Y') }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Départ</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $reservation->check_out->translatedFormat('D d M Y') }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Nuits</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $reservation->nights() }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Chambre</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $reservation->roomCategory->name }}@if($reservation->ratePlan) — {{ $reservation->ratePlan->name }}@endif</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Voyageurs</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $reservation->adults }} adulte(s){{ $reservation->children ? ', '.$reservation->children.' enfant(s)' : '' }} · {{ $reservation->rooms_count }} ch.</td></tr>
                    <tr><td style="padding:10px 0 6px;border-top:1px solid #e3e7ef;color:#596d7a;">Total estimé</td><td style="padding:10px 0 6px;border-top:1px solid #e3e7ef;text-align:right;font-weight:bold;">{{ money($reservation->total) }}</td></tr>
                    <tr><td style="padding:6px 0;color:#596d7a;">Acompte suggéré (30 %)</td><td style="padding:6px 0;text-align:right;font-weight:bold;">{{ money($reservation->deposit) }}</td></tr>
                </table>

                @if ($appUrl)
                    <div style="margin-top:22px;text-align:center;">
                        <a href="{{ $appUrl }}" style="display:inline-block;background:#de6443;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:999px;">
                            Gérer mon séjour en ligne
                        </a>
                        <div style="font-size:12px;color:#a2aeb4;margin-top:8px;">Folio, room service, demandes, fidélité — sans créer de compte.</div>
                    </div>
                @endif

                <p style="margin:22px 0 0;line-height:1.6;font-size:13px;color:#596d7a;">
                    Une question&nbsp;? Écrivez-nous à {{ $h['contact']['reservations_email'] }}
                    ou appelez le {{ $h['contact']['phone'] }}.
                </p>
            </td></tr>

            <tr><td style="background:#f8f9f7;padding:16px 28px;font-size:12px;color:#a2aeb4;">
                {{ $h['name'] }} — {{ $h['contact']['address_line'] }}, {{ $h['city'] }}, {{ $h['country'] }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
