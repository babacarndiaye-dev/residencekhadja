<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function roomCategories(): HasMany
    {
        return $this->hasMany(RoomCategory::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Hôtel courant (mono-établissement pour l'instant ; base multi-hôtels prête). */
    public static function current(): self
    {
        return static::query()->where('is_active', true)->orderBy('id')->firstOrFail();
    }
}
