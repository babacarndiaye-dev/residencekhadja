<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ReportSchedule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'recipients' => 'array',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public const FREQUENCIES = ['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel'];

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reportLabel(): string
    {
        return config("bi.reports.{$this->report_key}.label", $this->report_key);
    }

    public function isDue(Carbon $on): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->last_run_at && $this->last_run_at->isSameDay($on)) {
            return false;
        }

        return match ($this->frequency) {
            'daily' => true,
            'weekly' => $on->isMonday(),
            'monthly' => $on->day === 1,
            default => false,
        };
    }
}
