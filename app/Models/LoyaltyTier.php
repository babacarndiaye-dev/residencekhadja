<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyTier extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'perks' => 'array',
        'earn_rate' => 'float',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(LoyaltyAccount::class, 'tier_id');
    }
}
