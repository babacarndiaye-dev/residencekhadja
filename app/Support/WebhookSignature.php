<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Vérification de la signature HMAC-SHA256 d'un webhook entrant.
 *
 * Un prestataire (agrégateur de paiement, canal OTA) signe le corps brut de la
 * requête avec un secret partagé et transmet le résultat hexadécimal dans un
 * en-tête (par défaut « X-Signature », avec ou sans préfixe « sha256= »).
 *
 * Politique : la vérification n'est imposée que si un secret est configuré.
 * Sans secret (recette, connecteur « simulator »), la requête passe mais
 * l'appelant est invité à journaliser l'absence de protection via `enforced()`.
 */
class WebhookSignature
{
    /**
     * Vrai si la requête est authentique — signature valide, ou aucun secret configuré.
     */
    public static function valid(Request $request, ?string $secret, string $header = 'X-Signature'): bool
    {
        if (blank($secret)) {
            return true;
        }

        $provided = (string) ($request->header($header) ?: $request->input('signature', ''));
        $provided = strtolower(trim(preg_replace('/^sha256=/i', '', $provided)));

        if ($provided === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $provided);
    }

    /**
     * Vrai si un secret est configuré (donc si la signature est réellement contrôlée).
     */
    public static function enforced(?string $secret): bool
    {
        return filled($secret);
    }
}
