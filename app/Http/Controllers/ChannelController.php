<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Services\ChannelManager;
use App\Support\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChannelController extends Controller
{
    /**
     * Flux iCal des indisponibilités d'une catégorie — à importer dans un
     * calendrier externe ou un extranet OTA géré manuellement. Aucune donnée client.
     */
    public function ical(string $category)
    {
        $cat = RoomCategory::where('slug', $category)->firstOrFail();

        $stays = Reservation::where('room_category_id', $cat->id)
            ->whereIn('status', Reservation::BLOCKING)
            ->where('check_out', '>=', now()->subMonth()->toDateString())
            ->orderBy('check_in')
            ->get(['id', 'reference', 'check_in', 'check_out', 'rooms_count', 'channel']);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Residence Khadija//Channel Manager//FR',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:'.$cat->name.' — indisponibilités',
        ];

        foreach ($stays as $s) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:res-'.$s->id.'@residence-khadija.sn';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$s->check_in->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$s->check_out->format('Ymd');
            $lines[] = 'SUMMARY:Occupé ('.$s->rooms_count.' ch.)';
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="'.$category.'.ics"',
        ]);
    }

    /**
     * Notification de réservation entrante d'un canal (production).
     * Idempotent par (channel, external_ref). CSRF-exempt.
     */
    public function webhook(Request $request, string $channel)
    {
        $ch = Channel::where('key', $channel)->where('is_active', true)->first();
        if (! $ch) {
            return response()->json(['ok' => false, 'reason' => 'unknown_channel'], 202);
        }

        $secret = $ch->credentials['webhook_secret'] ?? config('distribution.webhook_secret');
        if (! WebhookSignature::valid($request, $secret, config('distribution.webhook_signature_header', 'X-Signature'))) {
            Log::warning('channel.webhook.bad_signature', ['channel' => $channel, 'ip' => $request->ip()]);

            return response()->json(['ok' => false, 'reason' => 'bad_signature'], 401);
        }
        if (! WebhookSignature::enforced($secret)) {
            Log::warning('channel.webhook.unverified', ['channel' => $channel]);
        }

        $data = $request->validate([
            'external_ref' => ['required', 'string', 'max:60'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email'],
            'country' => ['nullable', 'string', 'max:60'],
            'room_slug' => ['required', 'string'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:10'],
            'children' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'gross_amount' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $cr = ChannelManager::ingestReservation($ch, $data);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'reason' => $e->validator->errors()->first()], 409);
        }

        return response()->json([
            'ok' => true,
            'status' => $cr->status,
            'reference' => $cr->reservation?->reference,
        ]);
    }
}
