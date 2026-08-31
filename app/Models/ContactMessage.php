<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    /** Statuts possibles → libellé. */
    public const STATUSES = [
        'new' => 'Nouveau',
        'read' => 'Lu',
        'handled' => 'Traité',
    ];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function scopeUnhandled(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'read']);
    }
}
