<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expected_on' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft' => 'Brouillon',
        'submitted' => 'À valider',
        'approved' => 'Validée',
        'ordered' => 'Commandée',
        'partially_received' => 'Partiellement reçue',
        'received' => 'Reçue',
        'cancelled' => 'Annulée',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('status', ['received', 'cancelled']);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isFullyReceived(): bool
    {
        return $this->lines->every(fn ($l) => (float) $l->received_qty >= (float) $l->quantity);
    }
}
