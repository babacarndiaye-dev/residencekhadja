<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function typeLabel(): string
    {
        return config('stock.warehouse_types')[$this->type] ?? $this->type;
    }
}
