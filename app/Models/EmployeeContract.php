<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeContract extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function typeLabel(): string
    {
        return config('hr.contract_types')[$this->type] ?? $this->type;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->status === 'active' && $this->end_date
            && $this->end_date->isBetween(now(), now()->addDays($days));
    }
}
