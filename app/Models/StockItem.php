<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'min_qty' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StockCategory::class, 'stock_category_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('created_at');
    }

    public function menuItemRecipes(): HasMany
    {
        return $this->hasMany(MenuItemRecipe::class);
    }

    public function onHand(): float
    {
        return (float) $this->levels()->sum('quantity');
    }

    public function stockValue(): int
    {
        return (int) round($this->onHand() * $this->avg_cost);
    }

    public function isBelowThreshold(): bool
    {
        return $this->min_qty > 0 && $this->onHand() <= (float) $this->min_qty;
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
