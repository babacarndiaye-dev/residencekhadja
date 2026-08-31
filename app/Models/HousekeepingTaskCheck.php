<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingTaskCheck extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'passed' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(HousekeepingTask::class, 'housekeeping_task_id');
    }
}
