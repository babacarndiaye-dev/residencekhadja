<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['pin_hash'];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'notice_end_date' => 'date',
        'dependents_count' => 'integer',
        'leave_balance_days' => 'decimal:2',
        'tracks_attendance' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(JobPosition::class, 'job_position_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class)->latest('start_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest();
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class)->latest('start_date');
    }

    public function salaryComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('employment_status', ['active', 'on_leave']);
    }

    /** Employés soumis au pointage entrée / sortie à la borne. */
    public function scopeTracksAttendance(Builder $q): Builder
    {
        return $q->where('tracks_attendance', true);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function activeContract(): ?EmployeeContract
    {
        return $this->contracts()->where('status', 'active')->first()
            ?? $this->contracts()->first();
    }

    public function statusLabel(): string
    {
        return config('hr.employment_statuses')[$this->employment_status] ?? $this->employment_status;
    }

    public function outstandingAdvances(): int
    {
        return (int) $this->advances()->where('status', 'outstanding')
            ->selectRaw('sum(amount - repaid_amount) as d')->value('d');
    }

    public function isTerminated(): bool
    {
        return $this->employment_status === 'terminated';
    }

    public function maritalLabel(): string
    {
        return config('hr.marital_statuses')[$this->marital_status] ?? $this->marital_status;
    }

    public function terminationLabel(): ?string
    {
        return $this->termination_type
            ? (config('hr.termination_reasons')[$this->termination_type] ?? $this->termination_type)
            : null;
    }

    /** Années de présence révolues (ancienneté). */
    public function seniorityYears(): int
    {
        return $this->hire_date
            ? (int) $this->hire_date->diffInYears($this->termination_date ?? now())
            : 0;
    }
}
