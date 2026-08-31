<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinanceTransaction extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['operation_date' => 'date'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function categoryLabel(): string
    {
        return config('finance.income_categories')[$this->category]
            ?? config('finance.expense_categories')[$this->category]
            ?? $this->category;
    }

    public function signedAmount(): int
    {
        return $this->direction === 'income' ? $this->amount : -$this->amount;
    }
}
