<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoolAsset extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(PoolReservation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function kindLabel(): string
    {
        return config("pool.kinds.{$this->kind}", $this->kind);
    }

    public function priceFor(string $slot): int
    {
        return $slot === 'full_day' ? (int) $this->full_day_price : (int) $this->half_day_price;
    }
}
