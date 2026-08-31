<?php

namespace App\Models;

use App\Services\FinanceLedger;
use App\Services\LoyaltyProgram;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    /**
     * Chaque paiement client alimente le journal financier + la comptabilité (§40–41).
     * Ne fait rien tant que les comptes financiers n'existent pas (avant Phase 8).
     */
    protected static function booted(): void
    {
        static::created(function (Payment $payment): void {
            self::accrueLoyalty($payment);

            if (! Schema::hasTable('finance_accounts')
                || ! FinanceAccount::query()->exists()) {
                return;
            }

            $ref = $payment->reservation?->reference ?? '—';

            if ($payment->type === 'refund' || $payment->amount < 0) {
                FinanceLedger::record([
                    'direction' => 'expense',
                    'category' => 'divers_charges',
                    'method' => $payment->method,
                    'amount' => abs($payment->amount),
                    'label' => "Remboursement réservation {$ref}",
                    'operation_date' => $payment->received_at,
                    'source' => $payment,
                ]);

                return;
            }

            FinanceLedger::record([
                'direction' => 'income',
                'category' => 'hebergement',
                'method' => $payment->method,
                'amount' => $payment->amount,
                'label' => "Encaissement réservation {$ref}",
                'operation_date' => $payment->received_at,
                'source' => $payment,
            ]);
        });
    }

    public const METHODS = [
        'especes' => 'Espèces',
        'carte' => 'Carte bancaire',
        'virement' => 'Virement',
        'mobile' => 'Paiement mobile',
    ];

    public const TYPES = [
        'deposit' => 'Acompte',
        'balance' => 'Solde',
        'refund' => 'Remboursement',
    ];

    /** Crédite les points de fidélité si le client titulaire est inscrit (§56). */
    protected static function accrueLoyalty(Payment $payment): void
    {
        if (! Schema::hasTable('loyalty_accounts')) {
            return;
        }
        if ($payment->amount <= 0 || $payment->type === 'refund') {
            return;
        }

        $account = $payment->reservation?->guest?->loyaltyAccount;
        if (! $account) {
            return;
        }

        $ref = $payment->reservation?->reference ?? '—';
        LoyaltyProgram::earn($account, (int) $payment->amount, "Séjour {$ref}", $payment);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
