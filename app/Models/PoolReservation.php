<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolReservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'price' => 'integer',
        'guests' => 'integer',
    ];

    /** Créneaux qui se chevauchent (une journée bloque matinée + après-midi et inversement). */
    public const OVERLAP = [
        'morning' => ['morning', 'full_day'],
        'afternoon' => ['afternoon', 'full_day'],
        'full_day' => ['morning', 'afternoon', 'full_day'],
    ];

    public const BLOCKING = ['booked', 'checked_in'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(PoolAsset::class, 'pool_asset_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING);
    }

    public function slotLabel(): string
    {
        return config("pool.slots.{$this->slot}.label", $this->slot);
    }

    public function statusLabel(): string
    {
        return config("pool.statuses.{$this->status}", $this->status);
    }
}
