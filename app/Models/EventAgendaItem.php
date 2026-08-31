<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAgendaItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_min' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function categoryLabel(): string
    {
        return config("events.agenda_categories.{$this->category}", $this->category);
    }
}
