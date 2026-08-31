<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Trace une action. $on = modèle concerné (facultatif). */
    public static function record(string $action, ?Model $on = null, array $meta = []): void
    {
        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $on?->getMorphClass(),
            'auditable_id' => $on?->getKey(),
            'meta' => $meta ?: null,
            'ip' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
