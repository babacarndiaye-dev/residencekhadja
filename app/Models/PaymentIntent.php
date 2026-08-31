<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentIntent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
        'meta' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'paid' => 'Payé',
        'failed' => 'Échoué',
        'expired' => 'Expiré',
        'refunded' => 'Remboursé',
        'cancelled' => 'Annulé',
    ];

    public const PURPOSES = [
        'reservation_deposit' => 'Acompte réservation',
        'reservation_balance' => 'Solde réservation',
        'order' => 'Commande restaurant',
        'event_deposit' => 'Acompte événement',
        'event_balance' => 'Solde événement',
    ];

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function purposeLabel(): string
    {
        return self::PURPOSES[$this->purpose] ?? $this->purpose;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isRefundable(): bool
    {
        return $this->status === 'paid';
    }
}
