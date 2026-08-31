<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'allergens' => 'array',
        'tags' => 'array',
        'is_available' => 'boolean',
        'is_signature' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    /**
     * URL affichable de la photo : telle quelle si c'est une URL absolue,
     * sinon résolue depuis le disque public (fichier téléversé).
     */
    public function imageUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        return Str::startsWith($this->image, ['http://', 'https://', '//', '/'])
            ? $this->image
            : Storage::disk('public')->url($this->image);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(MenuOptionGroup::class)->orderBy('sort_order')->with('options');
    }

    public function recipe(): HasMany
    {
        return $this->hasMany(MenuItemRecipe::class);
    }

    /** Coût matière (FCFA) d'une unité vendue, d'après la recette. */
    public function foodCost(): int
    {
        return (int) round($this->recipe->sum(fn (MenuItemRecipe $r) => (float) $r->quantity * (int) $r->stockItem?->avg_cost));
    }

    /** Marge en % du prix de vente (null si pas de recette). */
    public function marginPercent(): ?float
    {
        if ($this->recipe->isEmpty() || $this->price <= 0) {
            return null;
        }

        return round(($this->price - $this->foodCost()) / $this->price * 100, 1);
    }

    public function scopeAvailable(Builder $q): Builder
    {
        return $q->where('is_available', true);
    }
}
