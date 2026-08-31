<?php

namespace App\Models;

use App\Services\ChannelManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Reservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'extras' => 'array',
        'special_requests' => 'array',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'pre_arrival_sent_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'À confirmer',
        'confirmed' => 'Confirmée',
        'checked_in' => 'En séjour',
        'checked_out' => 'Départ effectué',
        'cancelled' => 'Annulée',
        'no_show' => 'No-show',
    ];

    /** Statuts qui « occupent » de l'inventaire sur les dates. */
    public const BLOCKING = ['pending', 'confirmed', 'checked_in', 'checked_out'];

    /**
     * Au départ effectif d'un séjour issu d'un canal (OTA…), comptabilise
     * la commission de distribution (§31). Sans effet avant la Phase 14.
     */
    protected static function booted(): void
    {
        static::updated(function (Reservation $reservation): void {
            if (! $reservation->wasChanged('status') || $reservation->status !== 'checked_out') {
                return;
            }
            if (! Schema::hasTable('channel_reservations')) {
                return;
            }

            ChannelManager::postCommissionOnCheckout($reservation);
        });
    }

    /* ----------------------------- Relations ---------------------------- */

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /** Les autres chambres de la même réservation groupée (self incluse). */
    public function groupMembers()
    {
        return $this->group_reference
            ? static::query()->where('group_reference', $this->group_reference)->orderBy('id')
            : static::query()->whereKey($this->id);
    }

    /** Réservation multi-chambres (plusieurs lignes reliées par group_reference). */
    public function isGrouped(): bool
    {
        return filled($this->group_reference);
    }

    /** Référence à afficher : celle du groupe si groupée, sinon la sienne. */
    public function groupKey(): string
    {
        return $this->group_reference ?: $this->reference;
    }

    /**
     * Toutes les chambres du séjour (self incluse), triées, en une requête.
     * Une réservation simple renvoie une collection Eloquent d'un élément.
     *
     * @return Collection<int, Reservation>
     */
    public function groupSiblings(): Collection
    {
        $query = $this->isGrouped()
            ? static::query()->where('group_reference', $this->group_reference)
            : static::query()->whereKey($this->getKey());

        return $query->orderBy('id')->get();
    }

    /**
     * Membre « principal » du groupe (1re chambre) : porte le folio consolidé
     * — paiements, suppléments, remise, taxe de séjour, numéro de facture.
     */
    public function groupPrimary(): self
    {
        return $this->isGrouped()
            ? ($this->groupSiblings()->first() ?? $this)
            : $this;
    }

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ReservationCharge::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function guestRequests(): HasMany
    {
        return $this->hasMany(GuestRequest::class);
    }

    public function satisfactionSurvey(): HasOne
    {
        return $this->hasOne(SatisfactionSurvey::class);
    }

    /* ------------------------------ Scopes ----------------------------- */

    public function scopeArrivingOn(Builder $q, $date): Builder
    {
        return $q->whereDate('check_in', $date)->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeDepartingOn(Builder $q, $date): Builder
    {
        return $q->whereDate('check_out', $date)->whereIn('status', ['checked_in', 'confirmed']);
    }

    public function scopeInHouse(Builder $q): Builder
    {
        return $q->where('status', 'checked_in');
    }

    public function scopeStayingOn(Builder $q, $date): Builder
    {
        return $q->whereDate('check_in', '<=', $date)
            ->whereDate('check_out', '>', $date)
            ->whereIn('status', self::BLOCKING);
    }

    /* ------------------------------ Helpers ---------------------------- */

    public function nights(): int
    {
        return max(1, (int) $this->check_in->diffInDays($this->check_out));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paidAmount(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    /** Consommations imputées (restaurant, room service…). */
    public function chargesTotal(): int
    {
        return (int) $this->charges()->sum('amount');
    }

    /** Total séjour + extras de folio. */
    public function grandTotal(): int
    {
        return $this->total + $this->chargesTotal();
    }

    public function balance(): int
    {
        return $this->grandTotal() - $this->paidAmount();
    }

    public function canCheckIn(): bool
    {
        return in_array($this->status, ['confirmed', 'pending'], true);
    }

    public function canCheckOut(): bool
    {
        return $this->status === 'checked_in';
    }
}
