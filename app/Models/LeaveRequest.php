<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
        'cancelled' => 'Annulé',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function typeConfig(): array
    {
        return config("hr.leave_types.{$this->type}", ['label' => $this->type, 'paid' => true, 'deducts_balance' => false]);
    }

    public function typeLabel(): string
    {
        return $this->typeConfig()['label'];
    }

    public function isPaid(): bool
    {
        return (bool) $this->typeConfig()['paid'];
    }
}
