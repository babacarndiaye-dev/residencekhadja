<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public const STATUSES = [
        'open' => 'Nouvelle',
        'acknowledged' => 'Prise en compte',
        'done' => 'Traitée',
        'cancelled' => 'Annulée',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ReservationCharge::class, 'charge_id');
    }

    /** Définition du service du catalogue (config/guestapp.php), le cas échéant. */
    public function service(): ?array
    {
        if (! $this->service_slug) {
            return null;
        }

        return collect(config('guestapp.services'))->firstWhere('slug', $this->service_slug);
    }

    /** Montant imputé au folio pour cette demande (PU × quantité). */
    public function chargeAmount(): int
    {
        return (int) $this->price * max(1, (int) $this->quantity);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', ['open', 'acknowledged']);
    }

    public function typeLabel(): string
    {
        if ($this->service_slug) {
            $label = $this->service()['label'] ?? $this->service_slug;

            return $this->quantity > 1 ? "{$label} ×{$this->quantity}" : $label;
        }

        return config("guestapp.request_types.{$this->type}", $this->type);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
