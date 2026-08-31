<?php

namespace App\Services;

use App\Mail\PaymentReceipt;
use App\Models\Event;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Services\Payments\CinetPayDriver;
use App\Services\Payments\PayDunyaDriver;
use App\Services\Payments\PaymentDriver;
use App\Services\Payments\SimulatorDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Paiement en ligne (§25) — abstraction agnostique du prestataire.
 *
 * Le driver « simulator » n'appelle aucun service externe : la page de
 * paiement hébergée (PaymentCheckoutController) capture ou échoue l'intention.
 * Chaque capture solde la cible métier (réservation / commande / événement)
 * via les mécanismes existants (observer Payment → trésorerie + fidélité,
 * FinanceLedger pour les commandes et événements).
 */
class PaymentGateway
{
    /** Ouvre (ou réutilise) une intention de paiement pour une cible métier. */
    public static function open(Model $payable, string $purpose, ?int $amount = null, array $opts = []): PaymentIntent
    {
        abort_unless(array_key_exists($purpose, PaymentIntent::PURPOSES), 422, 'Objet de paiement inconnu.');

        $amount ??= self::defaultAmount($payable, $purpose);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Montant à payer nul.']);
        }

        $existing = $payable->morphMany(PaymentIntent::class, 'payable')
            ->where('purpose', $purpose)
            ->whereIn('status', ['pending', 'processing'])
            ->latest('id')
            ->first();

        if ($existing && $existing->isOpen()) {
            return tap($existing)->update(['amount' => $amount] + array_filter([
                'payer_name' => $opts['payer_name'] ?? null,
                'payer_email' => $opts['payer_email'] ?? null,
            ]));
        }

        $intent = PaymentIntent::create([
            'hotel_id' => $opts['hotel_id'] ?? (Hotel::current()->id),
            'reference' => self::nextReference(),
            'purpose' => $purpose,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'payer_name' => $opts['payer_name'] ?? null,
            'payer_email' => $opts['payer_email'] ?? null,
            'amount' => $amount,
            'currency' => config('payments.currency', 'XOF'),
            'provider' => config('payments.driver', 'simulator'),
            'status' => 'pending',
            'meta' => $opts['meta'] ?? null,
            'expires_at' => now()->addMinutes((int) config('payments.intent_ttl_minutes', 60)),
            'created_by' => $opts['created_by'] ?? null,
        ]);

        // Prestataire réel : on ouvre la session de paiement et on retient l'URL
        // de redirection + la référence prestataire. Le simulateur ne fait rien ici.
        if ($intent->provider !== 'simulator') {
            self::startProvider($intent);
        }

        return $intent;
    }

    /** Instancie le driver de paiement (par défaut : celui de config). */
    public static function driver(?string $name = null): PaymentDriver
    {
        return match ($name ?? config('payments.driver', 'simulator')) {
            'paydunya' => new PayDunyaDriver,
            'cinetpay' => new CinetPayDriver,
            default => new SimulatorDriver,
        };
    }

    /** Ouvre la session prestataire ; échoue l'intention proprement si l'API est indisponible. */
    protected static function startProvider(PaymentIntent $intent): void
    {
        try {
            $res = self::driver($intent->provider)->start($intent);
            $intent->update([
                'provider_ref' => $res['provider_ref'] ?? $intent->provider_ref,
                'meta' => array_merge($intent->meta ?? [], ['checkout_url' => $res['redirect_url']]),
            ]);
        } catch (Throwable $e) {
            Log::error('payment.provider.start_failed', [
                'intent' => $intent->reference, 'provider' => $intent->provider, 'error' => $e->getMessage(),
            ]);
            $intent->update(['status' => 'failed', 'failure_reason' => 'Ouverture du paiement impossible.']);
            throw ValidationException::withMessages(['payment' => 'Le service de paiement est momentanément indisponible.']);
        }
    }

    /**
     * Réconcilie une intention ouverte avec l'état réel chez le prestataire
     * (retour de page, ou repli si le webhook n'est pas arrivé).
     */
    public static function reconcile(PaymentIntent $intent): PaymentIntent
    {
        if (! $intent->isOpen()) {
            return $intent;
        }

        return match (self::driver($intent->provider)->verify($intent)) {
            'paid' => self::capture($intent, $intent->method ?? 'carte', $intent->provider_ref),
            'failed' => self::fail($intent, 'Paiement non abouti (vérification prestataire).'),
            default => $intent,
        };
    }

    /** Marque l'intention payée puis solde la cible métier. Idempotent. */
    public static function capture(PaymentIntent $intent, string $method = 'carte', ?string $providerRef = null): PaymentIntent
    {
        return DB::transaction(function () use ($intent, $method, $providerRef) {
            $intent = PaymentIntent::whereKey($intent->id)->lockForUpdate()->first();

            if ($intent->status === 'paid') {
                return $intent;                       // rejeu webhook / double clic
            }
            if (in_array($intent->status, ['refunded', 'cancelled'], true)) {
                throw ValidationException::withMessages(['status' => 'Intention clôturée.']);
            }

            $intent->update([
                'status' => 'paid',
                'method' => $method,
                'provider_ref' => $providerRef ?? $intent->provider_ref ?? ('SIM-'.Str::upper(Str::random(10))),
                'paid_at' => now(),
                'failure_reason' => null,
            ]);

            self::fulfil($intent->fresh());

            return $intent->fresh();
        });
    }

    public static function fail(PaymentIntent $intent, string $reason = 'Paiement refusé'): PaymentIntent
    {
        if ($intent->status === 'paid') {
            return $intent;
        }
        $intent->update(['status' => 'failed', 'failure_reason' => $reason]);

        return $intent;
    }

    /** Rembourse une intention payée. */
    public static function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        if (! $intent->isRefundable()) {
            throw ValidationException::withMessages(['status' => 'Seule une intention payée peut être remboursée.']);
        }

        $amount = min($amount ?? $intent->amount, $intent->amount);
        $payable = $intent->payable;

        DB::transaction(function () use ($intent, $amount, $payable) {
            if ($payable instanceof Reservation) {
                $payable->payments()->create([
                    'amount' => -$amount,
                    'method' => self::mapMethod($intent->method),
                    'type' => 'refund',
                    'reference' => $intent->reference.'-R',
                    'received_at' => now(),
                ]);
            } elseif ($payable) {
                FinanceLedger::record([
                    'direction' => 'expense',
                    'category' => 'divers_charges',
                    'method' => 'virement',
                    'amount' => $amount,
                    'label' => "Remboursement en ligne {$intent->reference}",
                    'operation_date' => now(),
                    'source' => $payable,
                ]);
            }

            $intent->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'meta' => array_merge($intent->meta ?? [], ['refunded_amount' => $amount]),
            ]);
        });

        return $intent->fresh();
    }

    /** Montant attendu par défaut selon la cible. */
    public static function defaultAmount(Model $payable, string $purpose): int
    {
        return match ($purpose) {
            'reservation_deposit' => (int) ($payable->deposit ?: round($payable->total * config('payments.reservation_deposit_rate'))),
            'reservation_balance' => (int) $payable->balance(),
            'order' => (int) $payable->total,
            'event_deposit' => (int) ($payable->quote?->deposit_amount ?? 0),
            'event_balance' => (int) $payable->balanceDue(),
            default => 0,
        };
    }

    public static function checkoutUrl(PaymentIntent $intent): string
    {
        // Prestataire réel : URL renvoyée par son API (retenue dans meta).
        // Simulateur : page de paiement hébergée locale.
        return $intent->meta['checkout_url'] ?? route('pay.checkout', $intent->reference);
    }

    /* ------------------------------------------------------------------ */

    protected static function fulfil(PaymentIntent $intent): void
    {
        $payable = $intent->payable;

        match (true) {
            $payable instanceof Reservation => self::fulfilReservation($intent, $payable),
            $payable instanceof Order => self::fulfilOrder($intent, $payable),
            $payable instanceof Event => self::fulfilEvent($intent, $payable),
            default => null,
        };

        self::emailReceipt($intent);
    }

    /** Reçu de paiement au payeur (si une adresse est connue). fulfil() est idempotent. */
    protected static function emailReceipt(PaymentIntent $intent): void
    {
        $to = $intent->payer_email ?: match (true) {
            $intent->payable instanceof Reservation => $intent->payable->guest?->email,
            $intent->payable instanceof Event => $intent->payable->lead?->contact_email,
            default => null,
        };

        if ($to) {
            Mail::to($to)->queue(new PaymentReceipt($intent));
        }
    }

    protected static function fulfilReservation(PaymentIntent $intent, Reservation $reservation): void
    {
        $reservation->payments()->create([
            'amount' => $intent->amount,
            'method' => self::mapMethod($intent->method),
            'type' => $intent->purpose === 'reservation_deposit' ? 'deposit' : 'balance',
            'reference' => $intent->reference,
            'received_at' => now(),
        ]);

        if ($reservation->status === 'pending' && $reservation->paidAmount() >= $reservation->deposit) {
            $reservation->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        }
    }

    protected static function fulfilOrder(PaymentIntent $intent, Order $order): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => in_array($intent->method, ['carte'], true) ? 'carte' : 'mobile',
        ]);

        FinanceLedger::record([
            'direction' => 'income',
            'category' => 'restaurant',
            'method' => self::mapMethod($intent->method),
            'amount' => $intent->amount,
            'label' => "Commande {$order->reference} — paiement en ligne",
            'operation_date' => now(),
            'source' => $order,
        ]);
    }

    protected static function fulfilEvent(PaymentIntent $intent, Event $event): void
    {
        $event->update($intent->purpose === 'event_deposit'
            ? ['deposit_invoiced' => true, 'deposit_paid' => true]
            : ['settled' => true]);

        FinanceLedger::record([
            'direction' => 'income',
            'category' => 'evenements',
            'method' => self::mapMethod($intent->method),
            'amount' => $intent->amount,
            'label' => ($intent->purpose === 'event_deposit' ? 'Acompte' : 'Solde')
                ." événement {$event->reference} — paiement en ligne",
            'operation_date' => now(),
            'source' => $event,
        ]);
    }

    /** Méthode de paiement en ligne → méthode trésorerie interne. */
    protected static function mapMethod(?string $method): string
    {
        return match ($method) {
            'carte' => 'carte',
            'orange_money', 'wave', 'free_money' => 'mobile',
            default => 'carte',
        };
    }

    protected static function nextReference(): string
    {
        do {
            $ref = 'PI-'.now()->format('ymd').Str::upper(Str::random(6));
        } while (PaymentIntent::where('reference', $ref)->exists());

        return $ref;
    }
}
