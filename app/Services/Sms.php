<?php

namespace App\Services;

use App\Jobs\SendSms;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de SMS transactionnels / marketing.
 *
 * Drivers (config/sms.php → `driver`) :
 *   log    — journalise seulement (défaut)
 *   http   — POST form générique { from, to, message } + Bearer token
 *   twilio — API Twilio (Basic auth, .../Messages.json)
 *   orange — Orange SMS API (OAuth2 client_credentials + /outbound/.../requests)
 *
 * `send()` est tolérant : toute exception passerelle est journalisée et renvoie
 * `false` sans interrompre l'appelant.
 */
class Sms
{
    public static function send(string $to, string $message, ?string $from = null): bool
    {
        $to = trim($to);
        if ($to === '') {
            return false;
        }

        $from ??= (string) config('sms.from', 'KHADIJA');
        $driver = config('sms.driver', 'log');

        try {
            return match ($driver) {
                'http' => self::viaHttp($to, $message, $from),
                'twilio' => self::viaTwilio(self::e164($to), $message, $from),
                'orange' => self::viaOrange(self::e164($to), $message),
                default => self::viaLog($to, $message, $from, $driver),
            };
        } catch (\Throwable $e) {
            Log::warning('sms.send.exception', [
                'driver' => $driver,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Met en file un SMS transactionnel à partir d'un modèle (config/sms.php →
     * `templates`). Ne fait rien si `sms.transactional` est faux ou si le
     * numéro est vide / invalide.
     *
     * @param  array<string, string|int>  $replace
     */
    public static function queueTemplate(?string $to, string $templateKey, array $replace = []): void
    {
        if (! config('sms.transactional', true)) {
            return;
        }

        $to = self::e164((string) $to);
        $message = self::template($templateKey, $replace);

        if ($to === '' || $message === '') {
            return;
        }

        SendSms::dispatch($to, $message);
    }

    /** Rend un modèle de SMS (`:cle` → valeur). */
    public static function template(string $key, array $replace = []): string
    {
        $text = (string) config("sms.templates.{$key}", '');
        if ($text === '') {
            return '';
        }

        foreach ($replace as $k => $v) {
            $text = str_replace(':'.$k, (string) $v, $text);
        }

        return $text;
    }

    /**
     * Normalise un numéro au format E.164 (« +indicatif… »).
     * Renvoie '' si le numéro ne contient pas assez de chiffres.
     */
    public static function e164(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $plus = str_starts_with($raw, '+') || str_starts_with(preg_replace('/\s+/', '', $raw), '00');
        $digits = preg_replace('/\D+/', '', $raw);
        $digits = preg_replace('/^0+/', '', $digits); // retire les zéros de tête (00 international, 0 national)

        if (strlen($digits) < 6) {
            return '';
        }

        // Numéro déjà international (préfixe + / 00, ou longueur ≥ 11).
        if ($plus || strlen($digits) >= 11) {
            return '+'.$digits;
        }

        return '+'.((string) config('sms.default_country', '221')).$digits;
    }

    /* --------------------------------------------------------------- drivers */

    private static function viaLog(string $to, string $message, string $from, string $driver): bool
    {
        Log::channel(config('logging.default'))->info('sms.send', [
            'driver' => $driver,
            'from' => $from,
            'to' => $to,
            'length' => mb_strlen($message),
            'message' => $message,
        ]);

        return true;
    }

    private static function viaHttp(string $to, string $message, string $from): bool
    {
        $endpoint = config('sms.http.endpoint');
        if (! $endpoint) {
            return self::viaLog($to, $message, $from, 'http(non configuré)');
        }

        $response = Http::withToken((string) config('sms.http.token'))
            ->asForm()
            ->post($endpoint, ['from' => $from, 'to' => $to, 'message' => $message]);

        if ($response->failed()) {
            Log::channel(config('logging.default'))->warning('sms.send.failed', [
                'driver' => 'http', 'to' => $to, 'status' => $response->status(),
            ]);
        }

        return $response->successful();
    }

    private static function viaTwilio(string $to, string $message, string $from): bool
    {
        $sid = (string) config('sms.twilio.sid');
        $token = (string) config('sms.twilio.token');

        if ($to === '' || $sid === '' || $token === '') {
            return self::viaLog($to, $message, $from, 'twilio(non configuré)');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('sms.twilio.from') ?: $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->failed()) {
            Log::channel(config('logging.default'))->warning('sms.send.failed', [
                'driver' => 'twilio', 'to' => $to, 'status' => $response->status(),
                'body' => $response->json('message') ?? $response->body(),
            ]);
        }

        return $response->successful();
    }

    private static function viaOrange(string $to, string $message): bool
    {
        $id = (string) config('sms.orange.client_id');
        $secret = (string) config('sms.orange.client_secret');
        $sender = (string) config('sms.orange.sender');

        if ($to === '' || $id === '' || $secret === '' || $sender === '') {
            return self::viaLog($to, $message, $sender, 'orange(non configuré)');
        }

        $accessToken = Cache::remember('sms.orange.token', now()->addMinutes(50), function () use ($id, $secret) {
            return Http::withBasicAuth($id, $secret)
                ->asForm()
                ->post((string) config('sms.orange.token_url'), ['grant_type' => 'client_credentials'])
                ->json('access_token');
        });

        if (! $accessToken) {
            Cache::forget('sms.orange.token');

            return false;
        }

        $url = rtrim((string) config('sms.orange.base_url'), '/').'/tel:'.rawurlencode($sender).'/requests';

        $response = Http::withToken($accessToken)->post($url, [
            'outboundSMSMessageRequest' => [
                'address' => 'tel:'.$to,
                'senderAddress' => 'tel:'.$sender,
                'senderName' => (string) config('sms.orange.sender_name'),
                'outboundSMSTextMessage' => ['message' => $message],
            ],
        ]);

        if ($response->status() === 401) {
            Cache::forget('sms.orange.token'); // jeton expiré : purge pour le prochain envoi
        }

        if ($response->failed()) {
            Log::channel(config('logging.default'))->warning('sms.send.failed', [
                'driver' => 'orange', 'to' => $to, 'status' => $response->status(),
            ]);
        }

        return $response->successful();
    }
}
