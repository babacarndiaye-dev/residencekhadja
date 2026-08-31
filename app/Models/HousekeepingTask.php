<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingTask extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'service_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'inspected_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'À faire',
        'in_progress' => 'En cours',
        'done' => 'Terminée',
        'blocked' => 'Bloquée',
        'inspected' => 'Contrôlée',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(HousekeepingTaskCheck::class)->orderBy('sort_order');
    }

    public function scopeForDate(Builder $q, $date): Builder
    {
        return $q->whereDate('service_date', $date);
    }

    public function typeLabel(): string
    {
        return config('housekeeping.task_types')[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
