<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventLead extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expected_start' => 'date',
        'expected_end' => 'date',
        'estimated_value' => 'integer',
        'pax' => 'integer',
    ];

    public const OPEN = ['nouveau', 'qualifie', 'devis', 'negociation'];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(EventLeadActivity::class)->orderByDesc('occurred_at');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(EventQuote::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function statusLabel(): string
    {
        return config("events.pipeline_stages.{$this->status}.label", $this->status);
    }

    public function typeLabel(): string
    {
        return config("events.event_types.{$this->event_type}", $this->event_type);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function openTasks(): HasMany
    {
        return $this->hasMany(EventLeadActivity::class)->where('type', 'task')->where('done', false);
    }
}
