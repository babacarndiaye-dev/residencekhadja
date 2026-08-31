<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Guest extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'birthdate' => 'date',
        'marketing_opt_in' => 'boolean',
        'consent_updated_at' => 'datetime',
        'tags' => 'array',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function loyaltyAccount(): HasOne
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(GuestInteraction::class)->latest('occurred_at');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Total dépensé sur les séjours honorés (CRM). */
    public function lifetimeValue(): int
    {
        return (int) $this->reservations()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->sum('total');
    }

    public function honouredStays(): int
    {
        return (int) $this->reservations()
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->count();
    }

    public function lastStayDate(): ?Carbon
    {
        return $this->reservations()
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->max('check_out') ? Carbon::parse(
                $this->reservations()->whereIn('status', ['checked_in', 'checked_out'])->max('check_out')
            ) : null;
    }

    public function isEnrolled(): bool
    {
        return $this->loyaltyAccount()->exists();
    }
}
