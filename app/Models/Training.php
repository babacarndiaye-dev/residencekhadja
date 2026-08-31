<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Training extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'training_participants')
            ->withPivot(['id', 'status', 'result']);
    }
}
