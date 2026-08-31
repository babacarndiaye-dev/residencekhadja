<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Statuts possibles → libellé. */
    public const STATUSES = [
        'libre' => 'Libre',
        'occupee' => 'Occupée',
        'sale' => 'Sale',
        'en_nettoyage' => 'En nettoyage',
        'propre' => 'Propre',
        'controle' => 'À contrôler',
        'bloquee' => 'Bloquée',
        'hors_service' => 'Hors service',
    ];

    /** Statuts considérés « prêts à vendre » pour une affectation au check-in. */
    public const ASSIGNABLE = ['libre', 'propre', 'controle'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isAssignable(): bool
    {
        return $this->is_active && in_array($this->status, self::ASSIGNABLE, true);
    }
}
