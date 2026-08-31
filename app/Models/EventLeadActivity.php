<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLeadActivity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'due_at' => 'datetime',
        'occurred_at' => 'datetime',
        'done' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(EventLead::class, 'event_lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return config("events.activity_types.{$this->type}", $this->type);
    }
}
