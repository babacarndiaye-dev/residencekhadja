<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\ChannelRateOverride;
use App\Models\ChannelRatePlan;
use App\Models\ChannelReservation;
use App\Models\ChannelSyncLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Services\Distribution\ChannelConnector;
use App\Services\Distribution\IcalConnector;
use App\Services\Distribution\SimulatorConnector;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Channel Manager (§29–31). Chaque canal porte un `connector` :
 *  - simulator : poussées ARI journalisées (channel_sync_logs), aucune API ;
 *  - ical      : import des blocages depuis une URL iCal (App\Services\Distribution\IcalConnector).
 * Les OTA à API certifiée restent sur « simulator » tant que l'accord n'est pas signé.
 */
class ChannelManager
{
    /** Instancie le connecteur d'un canal. */
    public static function connector(Channel|string $channel): ChannelConnector
    {
        $key = $channel instanceof Channel ? $channel->connector : $channel;

        return match ($key) {
            'ical' => new IcalConnector,
            default => new SimulatorConnector,
        };
    }

    /* --------------------------- Poussées ARI -------------------------- */

    public static function pushAvailability(Carbon $from, Carbon $to, ?Channel $only = null): int
    {
        return self::push('push_availability', $from, $to, $only,
            fn (Channel $channel) => self::connector($channel)->pushAvailability($channel, $from, $to));
    }

    public static function pushRates(Carbon $from, Carbon $to, ?Channel $only = null): int
    {
        return self::push('push_rates', $from, $to, $only,
            fn (Channel $channel) => self::connector($channel)->pushRates($channel, $from, $to));
    }

    /**
     * Récupère les réservations / blocages entrants de chaque canal (iCal…) et
     * les importe via ingestReservation(). Renvoie le nombre de canaux traités.
     */
    public static function pullReservations(?Channel $only = null): int
    {
        $channels = $only
            ? collect([$only])
            : Channel::where('is_active', true)->where('connector', '!=', 'direct')->get();

        $handled = 0;
        foreach ($channels as $channel) {
            $imported = 0;
            $failed = 0;
            try {
                $rows = self::connector($channel)->pullReservations($channel);
            } catch (Throwable $e) {
                ChannelSyncLog::create([
                    'channel_id' => $channel->id, 'action' => 'pull_reservation',
                    'status' => 'error', 'records' => 0, 'message' => 'Échec de récupération : '.$e->getMessage(),
                ]);

                continue;
            }

            foreach ($rows as $payload) {
                try {
                    $cr = self::ingestReservation($channel, $payload);
                    $cr->wasRecentlyCreated && $imported++;
                } catch (ValidationException) {
                    $failed++;
                }
            }

            ChannelSyncLog::create([
                'channel_id' => $channel->id, 'action' => 'pull_reservation', 'status' => 'ok',
                'records' => $imported,
                'message' => "{$imported} importée(s) · ".count($rows).' reçue(s)'.($failed ? " · {$failed} indisponible(s)" : ''),
            ]);
            $channel->update(['last_sync_at' => now()]);
            $handled++;
        }

        return $handled;
    }

    public static function priceFor(Channel $channel, RoomCategory $category, RatePlan $plan, Carbon $date): int
    {
        $override = ChannelRateOverride::where('channel_id', $channel->id)
            ->where('room_category_id', $category->id)
            ->whereDate('date', $date->toDateString())
            ->value('price');

        if ($override !== null) {
            return (int) $override;
        }

        $markup = (float) (ChannelRatePlan::where('channel_id', $channel->id)
            ->where('rate_plan_id', $plan->id)
            ->value('markup_rate') ?? 0);

        return (int) round($category->price * $plan->multiplier * (1 + $markup));
    }

    private static function push(string $action, Carbon $from, Carbon $to, ?Channel $only, \Closure $work): int
    {
        $channels = $only
            ? collect([$only])
            : Channel::where('is_active', true)->where('connector', '!=', 'direct')->get();

        $logs = 0;
        foreach ($channels as $channel) {
            $records = $work($channel);
            ChannelSyncLog::create([
                'channel_id' => $channel->id,
                'action' => $action,
                'status' => 'ok',
                'range_start' => $from->toDateString(),
                'range_end' => $to->toDateString(),
                'records' => $records,
                'message' => "{$records} enregistrement(s) poussé(s) ({$channel->connector})",
            ]);
            $channel->update(['last_sync_at' => now()]);
            $logs++;
        }

        return $logs;
    }

    /* --------------------- Réservations entrantes --------------------- */

    /**
     * Crée une réservation interne depuis une réservation de canal. Idempotent
     * par (channel, external_ref).
     *
     * @param  array{external_ref:string,first_name:string,last_name:string,email?:string,
     *               country?:string,room_slug:string,check_in:string,check_out:string,
     *               adults?:int,children?:int,rooms?:int,gross_amount:int}  $payload
     */
    public static function ingestReservation(Channel $channel, array $payload): ChannelReservation
    {
        $existing = ChannelReservation::where('channel_id', $channel->id)
            ->where('external_ref', $payload['external_ref'])
            ->first();

        if ($existing) {
            return $existing;                          // rejeu webhook
        }

        $category = RoomCategory::where('slug', $payload['room_slug'])->firstOrFail();
        $rooms = max(1, (int) ($payload['rooms'] ?? 1));

        // Contrôle hors transaction : la trace d'échec doit survivre au rollback.
        if (! Availability::canBook($category, $payload['check_in'], $payload['check_out'], $rooms)) {
            ChannelReservation::create([
                'channel_id' => $channel->id,
                'external_ref' => $payload['external_ref'],
                'status' => 'failed',
                'gross_amount' => (int) $payload['gross_amount'],
                'payload' => $payload,
            ]);
            throw ValidationException::withMessages(['availability' => 'Indisponible pour ces dates (stop-sell ou complet).']);
        }

        return DB::transaction(function () use ($channel, $payload, $category, $rooms) {
            if (! Availability::canBook($category, $payload['check_in'], $payload['check_out'], $rooms, lock: true)) {
                throw ValidationException::withMessages(['availability' => 'Indisponible (prise concurrente).']);
            }

            $guest = Guest::updateOrCreate(
                ['email' => $payload['email'] ?? Str::slug($payload['external_ref']).'@'.$channel->key.'.channel'],
                [
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                    'country' => $payload['country'] ?? null,
                    'acquisition_source' => 'ota',
                ],
            );

            $gross = (int) $payload['gross_amount'];
            $commission = (int) round($gross * $channel->commission_rate);

            $reservation = Reservation::create([
                'reference' => 'HRK-'.strtoupper(Str::random(6)),
                'hotel_id' => Hotel::current()->id,
                'guest_id' => $guest->id,
                'room_category_id' => $category->id,
                'status' => 'confirmed',
                'channel' => $channel->key,
                'check_in' => $payload['check_in'],
                'check_out' => $payload['check_out'],
                'adults' => max(1, (int) ($payload['adults'] ?? 2)),
                'children' => (int) ($payload['children'] ?? 0),
                'rooms_count' => $rooms,
                'currency' => 'XOF',
                'room_total' => $gross,
                'total' => $gross,
                'confirmed_at' => now(),
            ]);

            $cr = ChannelReservation::create([
                'channel_id' => $channel->id,
                'reservation_id' => $reservation->id,
                'external_ref' => $payload['external_ref'],
                'status' => 'imported',
                'gross_amount' => $gross,
                'commission_amount' => $commission,
                'payload' => $payload,
            ]);

            ChannelSyncLog::create([
                'channel_id' => $channel->id,
                'action' => 'pull_reservation',
                'status' => 'ok',
                'records' => 1,
                'message' => "Réservation {$reservation->reference} importée (réf. {$payload['external_ref']})",
            ]);

            return $cr;
        });
    }

    /** Comptabilise la commission d'un séjour de canal au départ du client. */
    public static function postCommissionOnCheckout(Reservation $reservation): void
    {
        if ($reservation->channel === 'direct' || ! $reservation->channel) {
            return;
        }

        $cr = ChannelReservation::where('reservation_id', $reservation->id)
            ->where('commission_posted', false)
            ->where('commission_amount', '>', 0)
            ->first();

        if (! $cr) {
            return;
        }

        FinanceLedger::record([
            'direction' => 'expense',
            'category' => 'commissions_ota',
            'method' => 'virement',
            'amount' => $cr->commission_amount,
            'label' => "Commission {$cr->channel->name} — séjour {$reservation->reference}",
            'operation_date' => now(),
            'source' => $reservation,
        ]);

        $cr->update(['commission_posted' => true]);
    }
}
