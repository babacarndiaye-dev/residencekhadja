<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['rate' => 'decimal:3'];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
