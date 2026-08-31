<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'option_expires_on' => 'date',
        'deposit_invoiced' => 'boolean',
        'deposit_paid' => 'boolean',
        'settled' => 'boolean',
        'pax' => 'integer',
        'rooms_to_block' => 'integer',
    ];

    public const STATUSES = [
        'option' => 'Option',
        'confirme' => 'Confirmé',
        'realise' => 'Réalisé',
        'annule' => 'Annulé',
    ];

    /** Statuts qui bloquent une salle sur son créneau. */
    public const BLOCKING = ['option', 'confirme', 'realise'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(EventLead::class, 'event_lead_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(EventQuote::class, 'event_quote_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function spaceBookings(): HasMany
    {
        return $this->hasMany(EventSpaceBooking::class);
    }

    public function agenda(): HasMany
    {
        return $this->hasMany(EventAgendaItem::class)->orderBy('scheduled_at')->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeInMonth(Builder $q, int $year, int $month): Builder
    {
        $start = now()->setDate($year, $month, 1)->startOfMonth();

        return $q->where('starts_at', '<=', $start->copy()->endOfMonth())
            ->where('ends_at', '>=', $start);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function typeLabel(): string
    {
        return config("events.event_types.{$this->event_type}", $this->event_type);
    }

    public function contractValue(): int
    {
        return (int) ($this->quote?->total ?? 0);
    }

    public function balanceDue(): int
    {
        $total = $this->contractValue();
        $paid = ($this->deposit_paid ? (int) ($this->quote?->deposit_amount ?? 0) : 0);

        return max(0, $total - $paid);
    }

    public function isOptionExpired(): bool
    {
        return $this->status === 'option'
            && $this->option_expires_on
            && $this->option_expires_on->isPast();
    }
}
