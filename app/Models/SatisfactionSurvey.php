<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatisfactionSurvey extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'reminded_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_on' => 'date',
        'handled_at' => 'datetime',
        'replied_at' => 'datetime',
        'published_at' => 'datetime',
        'category_ratings' => 'array',
        'consent_publish' => 'boolean',
        'is_published' => 'boolean',
        'rating_overall' => 'integer',
        'nps_score' => 'integer',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'received' => 'Reçu',
        'triaged' => 'Traité',
        'expired' => 'Expiré',
    ];

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /* --------------------------------------------------------------- scopes */

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->whereNotNull('completed_at');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)->whereNotNull('completed_at');
    }

    public function scopeAwaitingResponse(Builder $q): Builder
    {
        return $q->where('status', 'pending')->whereNull('completed_at');
    }

    /* ---------------------------------------------------------------- state */

    public function isOpen(): bool
    {
        return $this->completed_at === null
            && ($this->expires_on === null || ! $this->expires_on->isPast());
    }

    public function isDetractor(): bool
    {
        return $this->nps_score !== null && $this->nps_score <= 6;
    }

    public function isPromoter(): bool
    {
        return $this->nps_score !== null && $this->nps_score >= (int) config('satisfaction.promoter_score', 9);
    }

    public function needsAttention(): bool
    {
        return $this->completed_at !== null
            && ($this->isDetractor()
                || ($this->rating_overall !== null && $this->rating_overall <= (int) config('satisfaction.alert_at_or_below', 3)));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Note d'un critère (clé de config/satisfaction.categories). */
    public function categoryRating(string $key): ?int
    {
        $v = $this->category_ratings[$key] ?? null;

        return $v === null ? null : (int) $v;
    }

    /** Libellé d'auteur pour l'affichage public. */
    public function displayAuthor(): string
    {
        if ($this->author_label) {
            return $this->author_label;
        }

        $guest = $this->guest ?? $this->reservation?->guest;
        if ($guest) {
            $city = $guest->city ? ', '.$guest->city : '';

            return trim($guest->first_name.' '.mb_substr((string) $guest->last_name, 0, 1).'.').$city;
        }

        return 'Client vérifié';
    }
}
