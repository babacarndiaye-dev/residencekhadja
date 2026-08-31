<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class PromoCode extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'value' => 'integer',
        'max_redemptions' => 'integer',
        'redeemed_count' => 'integer',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class);
    }

    public function isRedeemable(): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->starts_on && $this->starts_on->isFuture()) {
            return false;
        }
        if ($this->ends_on && $this->ends_on->isPast()) {
            return false;
        }
        if ($this->max_redemptions !== null && $this->redeemed_count >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    /**
     * Codes promo actifs, indexés par code, au format attendu par BookingQuote
     * (fusionnés par-dessus config('booking.promo_codes')).
     *
     * @return array<string,array{type:string,value:int,label:string}>
     */
    public static function activeMap(): array
    {
        if (! Schema::hasTable('promo_codes')) {
            return [];
        }

        return static::query()->where('active', true)->get()
            ->filter->isRedeemable()
            ->mapWithKeys(fn (self $p) => [strtoupper($p->code) => [
                'type' => $p->type,
                'value' => $p->value,
                'label' => $p->label,
            ]])->all();
    }
}
