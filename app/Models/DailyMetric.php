<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'metric_date' => 'date',
        'value' => 'float',
        'meta' => 'array',
    ];
}
