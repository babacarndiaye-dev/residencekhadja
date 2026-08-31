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
                <div style="font-size:20px;font-weight:bold;margin-top:4px;">
                    {{ $reminder ? 'Votre avis compte pour nous' : 'Comment s’est passé votre séjour ?' }}
                </div>
            </td></tr>
            <tr><td style="padding:26px 28px;line-height:1.6;">
                <p style="margin:0 0 14px;">Bonjour {{ $firstName }},</p>
                <p style="margin:0 0 16px;">
                    @if ($reminder)
                        Vous n’avez peut-être pas eu le temps de nous répondre — il ne reste qu’une minute.
                    @else
                        Merci d’avoir choisi l’{{ $h['name'] }}. Votre retour nous aide à progresser
                        et guide les prochains voyageurs.
                    @endif
                </p>
                @if ($survey->reservation)
                    <p style="margin:0 0 16px;color:#596d7a;font-size:13px;">
                        Séjour réf. {{ $survey->reservation->reference }}
                        @if ($survey->reservation->roomCategory) · {{ $survey->reservation->roomCategory->name }} @endif
                    </p>
                @endif

                <div style="margin:22px 0;text-align:center;">
                    <a href="{{ $url }}" style="display:inline-block;background:#de6443;color:#fff;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:999px;">
                        Donner mon avis (1 min)
                    </a>
                    <div style="font-size:12px;color:#a2aeb4;margin-top:10px;">Aucun compte requis · lien personnel</div>
                </div>

                <p style="margin:16px 0 0;font-size:12px;color:#a2aeb4;">
                    Si le bouton ne fonctionne pas : <a href="{{ $url }}" style="color:#a73514;">{{ $url }}</a>
                </p>
                <p style="margin:18px 0 0;font-size:12px;color:#a2aeb4;">{{ $h['name'] }} · {{ $h['contact']['phone'] ?? '' }}</p>
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
