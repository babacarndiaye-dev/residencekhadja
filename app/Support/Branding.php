<?php

namespace App\Support;

/**
 * Identité visuelle (logo) de l'établissement.
 *
 * Comme App\Support\Splash, les surcharges vivent dans la table `site_settings`
 * sous des clés préfixées « branding. » — absentes du manifeste
 * config/settings.php, elles sont donc ignorées par SiteSettings::apply().
 * Tant qu'aucun fichier n'a été envoyé depuis le back-office, les vues
 * retombent sur les logos livrés dans public/img.
 */
class Branding
{
    public const PREFIX = 'branding.';

    /** Logo principal (couleur) — en-tête vitrine, back-office, documents, e-mails. */
    public static function logo(): string
    {
        return self::url('logo_path', 'img/logo-hrk.svg');
    }

    /** Logo monochrome (fonds sombres) — pied de page, badges, écrans d'auth. */
    public static function logoMono(): string
    {
        return self::url('logo_mono_path', 'img/logo-hrk-mono.svg');
    }

    /**
     * Chemins bruts enregistrés (ou null) — pour l'écran d'administration.
     *
     * @return array{logo: ?string, logo_mono: ?string}
     */
    public static function paths(): array
    {
        $map = SiteSettings::map();

        return [
            'logo' => $map[self::PREFIX.'logo_path'] ?? null,
            'logo_mono' => $map[self::PREFIX.'logo_mono_path'] ?? null,
        ];
    }

    /**
     * Enregistre les chemins de fichiers (clés `logo_path`, `logo_mono_path`).
     *
     * @param  array<string, string|null>  $values
     */
    public static function save(array $values, ?int $userId = null): void
    {
        $prefixed = [];
        foreach ($values as $key => $value) {
            $prefixed[self::PREFIX.$key] = $value === null ? null : (string) $value;
        }

        SiteSettings::put($prefixed, $userId);
    }

    private static function url(string $key, string $fallbackAsset): string
    {
        // La table est déjà en cache mémoire via SiteSettings::map() (invalidé à l'enregistrement).
        $path = SiteSettings::map()[self::PREFIX.$key] ?? null;

        // On passe par asset() (relatif à l'hôte de la requête, comme les logos livrés) et
        // NON par Storage::url() qui fige l'hôte sur APP_URL — l'image serait alors chargée
        // depuis le proxy borne (192.168.1.x:8443), injoignable en HTTP local → page figée.
        return asset($path ? 'storage/'.ltrim($path, '/') : $fallbackAsset);
    }
}
