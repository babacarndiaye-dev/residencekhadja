<?php

namespace App\Services;

use App\Models\GuestDevice;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Accès à l'application invité (§58). Le client se connecte avec sa
 * référence + son nom, ou via un lien magique signé remis à la réception.
 */
class GuestApp
{
    public const COOKIE = 'guest_token';

    /** Séjours qui donnent accès à l'app. */
    public const ACCESSIBLE = ['confirmed', 'checked_in'];

    public static function match(string $reference, string $lastName): ?Reservation
    {
        $reservation = Reservation::with('guest')
            ->whereRaw('UPPER(reference) = ?', [mb_strtoupper(trim($reference))])
            ->whereIn('status', self::ACCESSIBLE)
            ->first();

        if (! $reservation) {
            return null;
        }

        $given = Str::of($lastName)->lower()->ascii()->trim();
        $actual = Str::of($reservation->guest->last_name)->lower()->ascii()->trim();

        return $given->isNotEmpty() && $given->exactly((string) $actual) ? $reservation : null;
    }

    public static function issue(Reservation $reservation, Request $request): GuestDevice
    {
        return GuestDevice::create([
            'reservation_id' => $reservation->id,
            'token' => Str::random(48),
            'label' => Str::limit((string) $request->userAgent(), 80, ''),
            'ip' => $request->ip(),
            'last_seen_at' => now(),
            'expires_at' => now()->addDays((int) config('guestapp.token_ttl_days', 21)),
        ]);
    }

    public static function resolve(Request $request): ?GuestDevice
    {
        $token = $request->cookie(self::COOKIE);
        if (! $token) {
            return null;
        }

        $device = GuestDevice::with('reservation.guest')->where('token', $token)->first();

        if (! $device || ! $device->isValid()) {
            return null;
        }

        // Le séjour doit rester actif.
        if (! in_array($device->reservation->status, [...self::ACCESSIBLE, 'checked_out'], true)) {
            return null;
        }

        $device->forceFill(['last_seen_at' => now()])->saveQuietly();

        return $device;
    }

    /**
     * Lien magique — chemin signé *relatif* préfixé de l'adresse canonique (APP_URL),
     * pour rester valide quel que soit le point d'entrée (proxy HTTPS, 127.0.0.1…).
     */
    public static function magicUrl(Reservation $reservation): string
    {
        return qr_link(ltrim(
            URL::signedRoute('guest.magic', ['reference' => $reservation->reference], null, false),
            '/'
        ));
    }
}
