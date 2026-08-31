<?php

namespace App\Services\Distribution;

use App\Models\Channel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Connecteur iCal — import des blocages de calendrier (Airbnb, VRBO, export
 * « calendrier » de Booking.com…). Ne pousse rien (l'iCal est en lecture seule) ;
 * chaque VEVENT devient une réservation interne « confirmée » à montant nul qui
 * bloque l'inventaire, ce qui évite la surréservation.
 *
 * credentials attendues sur le canal : { "ical_url": "...", "room_slug": "..." }
 */
class IcalConnector implements ChannelConnector
{
    public function key(): string
    {
        return 'ical';
    }

    public function pushAvailability(Channel $channel, Carbon $from, Carbon $to): int
    {
        return 0;   // iCal = import seulement
    }

    public function pushRates(Channel $channel, Carbon $from, Carbon $to): int
    {
        return 0;
    }

    public function pullReservations(Channel $channel): array
    {
        $url = $channel->credentials['ical_url'] ?? null;
        $slug = $channel->credentials['room_slug'] ?? null;
        if (! $url || ! $slug) {
            return [];
        }

        $body = Http::timeout(20)->get($url)->throw()->body();
        $today = Carbon::today();
        $out = [];

        foreach ($this->events($body) as $ev) {
            if (mb_strtoupper($ev['STATUS'] ?? '') === 'CANCELLED') {
                continue;
            }
            $start = $this->date($ev['DTSTART'] ?? null);
            $end = $this->date($ev['DTEND'] ?? null);
            if (! $start || ! $end || $end->lte($start) || $end->lt($today)) {
                continue;
            }

            $uid = $ev['UID'] ?? md5(($ev['DTSTART'] ?? '').($ev['DTEND'] ?? '').($ev['SUMMARY'] ?? ''));
            $label = trim((string) ($ev['SUMMARY'] ?? '')) ?: 'Blocage calendrier';

            $out[] = [
                'external_ref' => $channel->key.':'.$uid,
                'first_name' => mb_substr($label, 0, 40),
                'last_name' => strtoupper($channel->key),
                'room_slug' => $slug,
                'check_in' => $start->toDateString(),
                'check_out' => $end->toDateString(),
                'adults' => 1,
                'rooms' => 1,
                'gross_amount' => 0,
            ];
        }

        return $out;
    }

    public function testConnection(Channel $channel): array
    {
        $url = $channel->credentials['ical_url'] ?? null;
        if (! $url) {
            return ['ok' => false, 'message' => 'Aucune URL iCal renseignée (credentials.ical_url).'];
        }
        if (empty($channel->credentials['room_slug'])) {
            return ['ok' => false, 'message' => 'Aucune catégorie cible (credentials.room_slug).'];
        }

        try {
            $body = Http::timeout(15)->get($url)->body();
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Injoignable : '.$e->getMessage()];
        }

        if (! str_contains($body, 'BEGIN:VCALENDAR')) {
            return ['ok' => false, 'message' => 'La réponse n’est pas un calendrier iCal.'];
        }

        $count = count($this->events($body));

        return ['ok' => true, 'message' => "Calendrier valide — {$count} évènement(s)."];
    }

    /* ------------------------------------------------------------------ */

    /** @return array<int, array<string, string>> */
    private function events(string $ics): array
    {
        // Déplie les lignes repliées (RFC 5545 : continuation = espace/tab en tête).
        $lines = preg_split('/\r\n|\r|\n/', $ics);
        $unfolded = [];
        foreach ($lines as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t") && $unfolded) {
                $unfolded[count($unfolded) - 1] .= substr($line, 1);
            } else {
                $unfolded[] = $line;
            }
        }

        $events = [];
        $cur = null;
        foreach ($unfolded as $line) {
            if ($line === 'BEGIN:VEVENT') {
                $cur = [];

                continue;
            }
            if ($line === 'END:VEVENT') {
                if ($cur !== null) {
                    $events[] = $cur;
                }
                $cur = null;

                continue;
            }
            if ($cur === null || ! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $key = strtoupper(explode(';', $name, 2)[0]);
            $cur[$key] = $value;
        }

        return $events;
    }

    private function date(?string $raw): ?Carbon
    {
        if (! $raw) {
            return null;
        }
        $raw = trim($raw);
        try {
            // Formats : 20260901 (DATE) · 20260901T140000Z / 20260901T140000 (DATE-TIME)
            if (preg_match('/^\d{8}$/', $raw)) {
                return Carbon::createFromFormat('Ymd', $raw)->startOfDay();
            }

            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
