<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeForDate(Builder $q, $date): Builder
    {
        return $q->whereDate('work_date', $date);
    }

    public function workedHours(): float
    {
        return round($this->worked_minutes / 60, 2);
    }

    public function overtimeHours(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }
}
