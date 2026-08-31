<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages du site éditables en back-office.
 *
 * Les valeurs vivent dans la table `site_settings` (clé → valeur) ; le
 * manifeste `config/settings.php` décrit les champs, leur type et le chemin
 * de config qu'ils surchargent. `apply()` est appelé au démarrage
 * (AppServiceProvider) : aucune vue ni aucun service n'a besoin de changer,
 * ils continuent de lire `config('hotel.name')`, `config('booking.tax_rate')`…
 */
class SiteSettings
{
    private const CACHE_KEY = 'site_settings.map';

    /** @return array<string, string> clé → valeur enregistrée */
    public static function map(): array
    {
        if (! Schema::hasTable('site_settings')) {
            return [];
        }

        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => DB::table('site_settings')->pluck('value', 'key')->all(),
        );
    }

    /** Surcharge la config avec les valeurs enregistrées (démarrage app). */
    public static function apply(): void
    {
        $map = self::map();
        if (! $map) {
            return;
        }

        foreach (self::fields() as $field) {
            if (! array_key_exists($field['key'], $map)) {
                continue;
            }
            $configKey = $field['config'] ?? $field['key'];
            config([$configKey => self::cast($map[$field['key']], $field['cast'] ?? 'string')]);
        }
    }

    /**
     * Enregistre un lot de valeurs (clé → valeur brute) et vide le cache.
     *
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values, ?int $userId = null): void
    {
        $now = now();

        $rows = collect($values)->map(fn ($value, $key) => [
            'key' => $key,
            'value' => ($value === null || $value === '') ? null : (string) $value,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        if ($rows) {
            DB::table('site_settings')->upsert($rows, ['key'], ['value', 'updated_by', 'updated_at']);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** Valeur effective d'un champ (enregistrée, sinon celle du fichier config). */
    public static function value(array $field): mixed
    {
        $map = self::map();
        $configKey = $field['config'] ?? $field['key'];

        return array_key_exists($field['key'], $map)
            ? self::cast($map[$field['key']], $field['cast'] ?? 'string')
            : config($configKey);
    }

    /** @return array<int, array<string, mixed>> tous les champs du manifeste, à plat */
    public static function fields(): array
    {
        return collect(config('settings.groups', []))
            ->flatMap(fn ($group) => $group['fields'])
            ->all();
    }

    private static function cast(?string $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $raw,
            'float' => (float) $raw,
            'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            default => $raw,
        };
    }
}
