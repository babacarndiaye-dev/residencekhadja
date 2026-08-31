<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class);
    }

    public function typeLabel(): string
    {
        return config('finance.account_types')[$this->type] ?? $this->type;
    }

    /** Solde courant = solde d'ouverture + entrées − sorties. */
    public function balance(): int
    {
        $in = (int) $this->transactions()->where('direction', 'income')->sum('amount');
        $out = (int) $this->transactions()->where('direction', 'expense')->sum('amount');

        return $this->opening_balance + $in - $out;
    }
}
