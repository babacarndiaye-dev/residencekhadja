<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'placed_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'invoiced_at' => 'datetime',
        'stock_applied_at' => 'datetime',
    ];

    public const STATUSES = [
        'new' => 'Nouvelle',
        'preparing' => 'En préparation',
        'ready' => 'Prête',
        'out_for_delivery' => 'En livraison',
        'delivered' => 'Livrée',
        'served' => 'Servie',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
    ];

    /** Transition suivante « avancer la commande » (service en salle). */
    public const NEXT = [
        'new' => 'preparing',
        'preparing' => 'ready',
        'ready' => 'served',
        'served' => 'completed',
    ];

    /** Pipeline room service : la commande part en livraison avant d'être clôturée. */
    public const NEXT_ROOM_SERVICE = [
        'new' => 'preparing',
        'preparing' => 'ready',
        'ready' => 'out_for_delivery',
        'out_for_delivery' => 'delivered',
        'delivered' => 'completed',
    ];

    public const OPEN = ['new', 'preparing', 'ready'];

    /** Statut suivant selon le type de commande. */
    public function nextStatus(): ?string
    {
        $map = $this->type === 'room_service' ? self::NEXT_ROOM_SERVICE : self::NEXT;

        return $map[$this->status] ?? null;
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(QrLocation::class, 'qr_location_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(User::class, 'server_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', self::OPEN);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid', 'charged_to_room'], true);
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    /** Somme des règlements (les remboursements sont négatifs). */
    public function amountPaid(): int
    {
        return (int) $this->payments()->sum('amount');
    }
}
