<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_taxable' => 'boolean',
        'applies_to_all' => 'boolean',
        'default_rate' => 'decimal:3',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function isEarning(): bool
    {
        return $this->kind === 'earning';
    }
}
