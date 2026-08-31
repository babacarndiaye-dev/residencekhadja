<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventQuote extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'tax_rate' => 'float',
        'deposit_rate' => 'float',
        'subtotal' => 'integer',
        'discount_amount' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
        'deposit_amount' => 'integer',
        'pax' => 'integer',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyé',
        'accepted' => 'Accepté',
        'declined' => 'Refusé',
        'expired' => 'Expiré',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(EventLead::class, 'event_lead_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EventQuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function event(): HasOne
    {
        return $this->hasOne(Event::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && $this->status !== 'accepted';
    }
}
