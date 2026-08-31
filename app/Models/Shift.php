<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Shift extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public const STATUSES = [
        'planned' => 'Planifié',
        'confirmed' => 'Confirmé',
        'swapped' => 'Remplacé',
        'cancelled' => 'Annulé',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'replacement_employee_id');
    }

    public function scopeForWeek(Builder $q, $monday): Builder
    {
        return $q->whereBetween('work_date', [
            Carbon::parse($monday)->toDateString(),
            Carbon::parse($monday)->addDays(6)->toDateString(),
        ]);
    }

    public function plannedMinutes(): int
    {
        return max(0, (int) $this->start_at->diffInMinutes($this->end_at) - $this->break_minutes);
    }

    /** Chevauche un autre shift du même employé ? */
    public function overlaps(Shift $other): bool
    {
        return $this->employee_id === $other->employee_id
            && $this->id !== $other->id
            && $this->start_at < $other->end_at
            && $this->end_at > $other->start_at;
    }
}
