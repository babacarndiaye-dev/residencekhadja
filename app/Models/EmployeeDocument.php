<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function categoryLabel(): string
    {
        return config('hr.document_categories')[$this->category] ?? $this->category;
    }
}
