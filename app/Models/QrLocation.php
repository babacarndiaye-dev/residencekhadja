<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrLocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Tables de restaurant actives (le plan de salle). */
    public function scopeTables(Builder $q): Builder
    {
        return $q->where('type', 'table')->where('is_active', true);
    }

    /** Ticket POS en cours (non réglé) sur cette table, s'il y en a un. */
    public function currentOrder(): ?Order
    {
        return $this->orders()
            ->where('source', 'pos')
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('payment_status', 'unpaid')
            ->latest('placed_at')
            ->first();
    }

    public function occupancyStatus(): string
    {
        return $this->currentOrder() ? 'occupee' : 'libre';
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isRoomService(): bool
    {
        return $this->type === 'room';
    }

    /** URL publique encodée dans le QR — toujours l'adresse canonique (APP_URL). */
    public function url(): string
    {
        return qr_link('carte/'.$this->code);
    }
}
