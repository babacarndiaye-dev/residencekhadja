<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelReservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'gross_amount' => 'integer',
        'commission_amount' => 'integer',
        'commission_posted' => 'boolean',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
