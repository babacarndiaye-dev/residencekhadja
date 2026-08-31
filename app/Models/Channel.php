<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'commission_rate' => 'float',
        'credentials' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function ratePlans(): HasMany
    {
        return $this->hasMany(ChannelRatePlan::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ChannelReservation::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(ChannelSyncLog::class)->latest('id');
    }

    public function isDirect(): bool
    {
        return $this->key === 'direct' || $this->connector === 'direct';
    }

    public function typeLabel(): string
    {
        return config("distribution.channel_types.{$this->type}", $this->type);
    }
}
