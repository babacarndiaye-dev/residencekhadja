<?php

use Carbon\Carbon;

if (! function_exists('money')) {
    /**
     * Formate un montant en FCFA à la française : 45 000 FCFA.
     */
    function money(int|float $amount, bool $withCurrency = true): string
    {
        $formatted = number_format((float) $amount, 0, ',', "\u{202F}"); // espace fine insécable

        return $withCurrency
            ? $formatted."\u{202F}".config('hotel.currency.symbol', 'FCFA')
            : $formatted;
    }
}

if (! function_exists('nights_between')) {
    /**
     * Nombre de nuits entre deux dates (chaînes Y-m-d ou objets date).
     */
    function nights_between($checkIn, $checkOut): int
    {
        try {
            $in = $checkIn instanceof Carbon ? $checkIn->copy()->startOfDay() : Carbon::parse($checkIn)->startOfDay();
            $out = $checkOut instanceof Carbon ? $checkOut->copy()->startOfDay() : Carbon::parse($checkOut)->startOfDay();
        } catch (Throwable) {
            return 0;
        }

        return max(0, $in->diffInDays($out, false));
    }
}

if (! function_exists('qr_link')) {
    /**
     * URL absolue construite depuis APP_URL (adresse canonique du réseau),
     * indépendamment du point d'entrée réel. À utiliser UNIQUEMENT pour les
     * liens destinés à être encodés dans un QR code ou imprimés / partagés —
     * jamais pour le rendu des pages (sinon les assets cassent en HTTP local).
     */
    function qr_link(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}

if (! function_exists('pretty_date')) {
    /**
     * Date lisible en français : « lun. 12 mai 2026 ».
     */
    function pretty_date($date): string
    {
        try {
            return Carbon::parse($date)->locale('fr')->isoFormat('ddd D MMM YYYY');
        } catch (Throwable) {
            return (string) $date;
        }
    }
}
