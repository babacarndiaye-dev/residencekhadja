<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelSyncLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'range_start' => 'date',
        'range_end' => 'date',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (ChannelSyncLog $l) => $l->created_at ??= now());
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function actionLabel(): string
    {
        return [
            'push_availability' => 'Poussée disponibilité',
            'push_rates' => 'Poussée tarifs',
            'pull_reservation' => 'Réservation entrante',
        ][$this->action] ?? $this->action;
    }
}
