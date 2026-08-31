<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSpace extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'layouts' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventSpaceBooking::class);
    }

    public function maxCapacity(): int
    {
        return (int) collect($this->layouts ?? [])->max() ?: 0;
    }
}
