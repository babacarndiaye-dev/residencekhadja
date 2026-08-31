<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakfastCheckin extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'service_date' => 'date',
        'guests' => 'integer',
        'included' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ReservationCharge::class, 'reservation_charge_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
