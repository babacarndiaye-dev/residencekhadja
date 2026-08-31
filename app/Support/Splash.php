<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Écran d'accueil de la vitrine (§ intro premium).
 *
 * Les réglages par défaut vivent dans `config/splash.php`. Le back-office
 * (Paramètres → Écran d'accueil) enregistre ses surcharges dans la table
 * `site_settings` sous des clés préfixées « splash. », via l'infrastructure
 * existante `App\Support\SiteSettings` (upsert + cache). On ne touche donc
 * ni au schéma ni au cache : `SiteSettings::apply()` ignore ces clés
 * (absentes du manifeste), elles ne sont lues que par cette classe.
 */
class Splash
{
    public const PREFIX = 'splash.';

    /** Réglages effectifs : défauts de config surchargés par la base. */
    public static function all(): array
    {
        $defaults = config('splash', []);
        $map = SiteSettings::map();
        $out = $defaults;

        foreach ($defaults as $key => $default) {
            $dbKey = self::PREFIX.$key;
            if (array_key_exists($dbKey, $map) && $map[$dbKey] !== null) {
                $out[$key] = self::cast($map[$dbKey], $default);
            }
        }

        return $out;
    }

    public static function enabled(): bool
    {
        return (bool) (self::all()['enabled'] ?? false);
    }

    /** Valeurs prêtes pour la vue : logo résolu, nom d'hôtel, durée bornée. */
    public static function view(): array
    {
        $s = self::all();

        $s['logo_url'] = ! empty($s['logo_path'])
            ? Storage::disk('public')->url($s['logo_path'])
            : Branding::logo();

        $s['hotel_name'] = trim((string) ($s['hotel_name'] ?? '')) ?: config('hotel.name');
        $s['duration_ms'] = max(1000, min(6000, (int) ($s['duration_ms'] ?? 2600)));
        $s['animation'] = in_array($s['animation'] ?? null, self::animations(), true) ? $s['animation'] : 'cinematic';

        return $s;
    }

    /** @param  array<string, mixed>  $values  clés sans préfixe */
    public static function save(array $values, ?int $userId = null): void
    {
        $prefixed = [];
        foreach ($values as $key => $value) {
            $prefixed[self::PREFIX.$key] = match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value === null => null,
                default => (string) $value,
            };
        }

        SiteSettings::put($prefixed, $userId);
    }

    /** @return list<string> */
    public static function animations(): array
    {
        return ['cinematic', 'fade', 'zoom', 'minimal'];
    }

    private static function cast(string $raw, mixed $default): mixed
    {
        return match (true) {
            is_bool($default) => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            is_int($default) => (int) $raw,
            default => $raw,
        };
    }
}
