<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;

/**
 * Contrat d'un prestataire de paiement en ligne.
 *
 * `simulator` = page hébergée locale, aucun appel externe.
 * `paydunya` / `cinetpay` = redirection vers la page du prestataire puis
 * confirmation par webhook et/ou vérification serveur-à-serveur.
 */
interface PaymentDriver
{
    /** Clé du driver (doit correspondre à une entrée de config/payments.providers). */
    public function key(): string;

    /**
     * Prépare le paiement côté prestataire et renvoie l'URL de redirection
     * du client + la référence prestataire éventuelle.
     *
     * @return array{redirect_url: string, provider_ref: ?string}
     */
    public function start(PaymentIntent $intent): array;

    /**
     * Interroge le prestataire sur l'état d'une intention (retour de page,
     * ou repli si le webhook n'arrive pas).
     *
     * @return 'paid'|'pending'|'failed'
     */
    public function verify(PaymentIntent $intent): string;

    /**
     * Analyse + authentifie une notification native du prestataire.
     * Renvoie null si la charge utile n'est pas au format de ce prestataire
     * (le contrôleur retombe alors sur la vérification HMAC générique).
     *
     * @return array{reference: ?string, provider_ref: ?string, status: string}|null
     */
    public function parseNativeWebhook(Request $request): ?array;
}
