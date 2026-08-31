<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie de chambre commercialisée.
 *
 * Les vues vitrine accèdent aux attributs en notation tableau
 * ($category['images'][0], $category['price']…) : Eloquent implémente
 * ArrayAccess, les noms de colonnes sont donc alignés sur les clés
 * historiques de config/rooms.php.
 */
class RoomCategory extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array',
        'featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /** Nombre de chambres physiques exploitables dans la catégorie. */
    public function sellableRoomsCount(): int
    {
        return $this->rooms()
            ->where('is_active', true)
            ->whereNotIn('status', ['hors_service'])
            ->count();
    }
}
