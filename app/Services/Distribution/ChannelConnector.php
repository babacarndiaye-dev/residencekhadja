<?php

namespace App\Services\Distribution;

use App\Models\Channel;
use Illuminate\Support\Carbon;

/**
 * Contrat d'un connecteur de canal de distribution.
 *
 *  - simulator : journalise les poussées ARI (aucune API).
 *  - ical      : import des blocages depuis un calendrier iCal (Airbnb, VRBO,
 *                export « calendrier » de Booking.com…). Pas de poussée sortante.
 *
 * Les OTA à API certifiée (Booking.com Connectivity, Expedia EPS, Airbnb API)
 * restent sur « simulator » tant que l'accord partenaire n'est pas en place.
 */
interface ChannelConnector
{
    public function key(): string;

    /** @return int nombre d'enregistrements poussés (0 si non supporté) */
    public function pushAvailability(Channel $channel, Carbon $from, Carbon $to): int;

    /** @return int nombre d'enregistrements poussés (0 si non supporté) */
    public function pushRates(Channel $channel, Carbon $from, Carbon $to): int;

    /**
     * Réservations / blocages entrants, normalisés pour ChannelManager::ingestReservation().
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullReservations(Channel $channel): array;

    /** @return array{ok: bool, message: string} */
    public function testConnection(Channel $channel): array;
}
