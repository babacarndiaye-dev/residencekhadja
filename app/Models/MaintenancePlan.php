<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'checklist' => 'array',
        'last_run_on' => 'date',
        'next_due_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function scopeDue(Builder $q, $date = null): Builder
    {
        return $q->where('is_active', true)->whereDate('next_due_on', '<=', $date ?? now());
    }

    public function categoryLabel(): string
    {
        return config('maintenance.equipment_categories')[$this->equipment_category] ?? $this->equipment_category;
    }
}
