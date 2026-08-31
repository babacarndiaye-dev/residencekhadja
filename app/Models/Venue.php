<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'accepts_qr_orders' => 'boolean',
        'is_room_service' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(QrLocation::class);
    }

    public function menuCategories(): BelongsToMany
    {
        return $this->belongsToMany(MenuCategory::class)->orderBy('sort_order');
    }
}
