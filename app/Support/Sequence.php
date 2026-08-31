<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Compteurs séquentiels atomiques (numéros de facture, etc.).
 */
class Sequence
{
    /** Prochaine valeur pour la clé donnée (créée à 1 si absente), puis incrémentée. */
    public static function next(string $key): int
    {
        return DB::transaction(function () use ($key) {
            $row = DB::table('sequences')->where('key', $key)->lockForUpdate()->first();

            if (! $row) {
                DB::table('sequences')->insert([
                    'key' => $key, 'next_value' => 2, 'created_at' => now(), 'updated_at' => now(),
                ]);

                return 1;
            }

            DB::table('sequences')->where('key', $key)->update([
                'next_value' => $row->next_value + 1, 'updated_at' => now(),
            ]);

            return (int) $row->next_value;
        });
    }
}
