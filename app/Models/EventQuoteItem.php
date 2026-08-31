<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventQuoteItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'integer',
        'total' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(EventQuote::class, 'event_quote_id');
    }

    public function categoryLabel(): string
    {
        return config("events.quote_item_categories.{$this->category}", $this->category);
    }
}
