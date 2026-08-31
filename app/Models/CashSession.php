<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_denominations' => 'array',
        'closing_denominations' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Montant théorique en caisse = fond + entrées − sorties de la session. */
    public function expected(): int
    {
        $in = (int) $this->transactions()->where('direction', 'income')->sum('amount');
        $out = (int) $this->transactions()->where('direction', 'expense')->sum('amount');

        return $this->opening_float + $in - $out;
    }

    /**
     * Rapport X (en cours) / Z (clôture) de la session : chiffre d'affaires
     * ventilé par moyen de paiement et par type de vente, remises et
     * remboursements, écart de caisse.
     *
     * @return array<string,mixed>
     */
    public function report(): array
    {
        $orders = $this->orders()->with('payments')->get();
        $active = $orders->where('status', '!=', 'cancelled');

        $byMethod = [];
        $refunds = 0;
        foreach ($active->flatMap->payments as $p) {
            $byMethod[$p->method] = ($byMethod[$p->method] ?? 0) + $p->amount;
            if ($p->amount < 0) {
                $refunds += -$p->amount;
            }
        }

        $byType = $active->groupBy('sale_type')->map(fn ($g) => (int) $g->sum('total'));

        return [
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'opening_float' => (int) $this->opening_float,
            'orders_count' => $active->count(),
            'gross_sales' => (int) $active->sum('total'),
            'discounts' => (int) $active->sum('discount'),
            'refunds' => $refunds,
            'by_method' => $byMethod,
            'by_type' => $byType,
            'cash_expected' => $this->expected(),
            'counted_amount' => $this->counted_amount,
            'variance' => $this->variance,
        ];
    }
}
