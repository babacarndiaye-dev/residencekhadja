<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
    ];

    public const STATUSES = [
        'unpaid' => 'À payer',
        'partially_paid' => 'Partiellement payée',
        'paid' => 'Payée',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function scopeOutstandingScope(Builder $q): Builder
    {
        return $q->whereIn('status', ['unpaid', 'partially_paid']);
    }

    public function balance(): int
    {
        return $this->total - $this->paid_amount;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
