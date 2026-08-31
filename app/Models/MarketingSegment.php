<?php

namespace App\Models;

use App\Services\Segmentation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSegment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'definition' => 'array',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingCampaign::class, 'segment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Nombre de clients actuellement dans le segment. */
    public function size(): int
    {
        return Segmentation::query($this->definition ?? [])->count();
    }

    public function rulesSummary(): string
    {
        $labels = config('marketing.segment_rules');
        $parts = [];
        foreach ($this->definition ?? [] as $key => $value) {
            $label = $labels[$key] ?? $key;
            $parts[] = is_bool($value) ? $label : "{$label} {$value}";
        }

        return implode(' · ', $parts) ?: 'Tous les clients';
    }
}
