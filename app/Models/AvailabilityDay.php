<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityDay extends Model
{
    protected $table = 'availability_calendar';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'rooms_open' => 'integer',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'cta' => 'boolean',
        'ctd' => 'boolean',
        'stop_sell' => 'boolean',
    ];

    public function roomCategory(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function hasRestriction(): bool
    {
        return $this->stop_sell || $this->cta || $this->ctd || $this->min_stay > 1 || $this->max_stay;
    }
}
