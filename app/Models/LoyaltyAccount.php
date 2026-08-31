<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest('id');
    }

    /** Valeur du solde en FCFA (remise mobilisable à la réception). */
    public function balanceValue(): int
    {
        return $this->points_balance * (int) config('loyalty.point_value_fcfa', 5);
    }

    public function pointsToNextTier(): ?array
    {
        $next = LoyaltyTier::where('hotel_id', $this->hotel_id)
            ->where('min_points', '>', $this->lifetime_points)
            ->orderBy('min_points')->first();

        return $next ? ['tier' => $next, 'missing' => $next->min_points - $this->lifetime_points] : null;
    }
}
