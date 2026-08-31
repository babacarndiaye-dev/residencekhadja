<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Programme de fidélité « Khadija Privilège » (§55–57).
 * Crédit automatique à l'encaissement, rachat à la réception, paliers dynamiques.
 */
class LoyaltyProgram
{
    /** Inscrit un client (idempotent). */
    public static function enroll(Guest $guest): LoyaltyAccount
    {
        if ($guest->loyaltyAccount) {
            return $guest->loyaltyAccount;
        }

        $hotel = Hotel::current();

        $account = LoyaltyAccount::create([
            'hotel_id' => $hotel->id,
            'guest_id' => $guest->id,
            'member_no' => self::nextMemberNo(),
            'points_balance' => 0,
            'lifetime_points' => 0,
            'enrolled_at' => now(),
        ]);

        return tap($account)->update(['tier_id' => self::tierFor($account)?->id]);
    }

    /** Crédit de points sur un montant dépensé (FCFA). */
    public static function earn(LoyaltyAccount $account, int $amountFcfa, string $reason, $source = null): ?LoyaltyTransaction
    {
        if ($amountFcfa <= 0) {
            return null;
        }

        $rate = (float) ($account->tier?->earn_rate ?? 1.0);
        $points = (int) floor($amountFcfa / 1000 * config('loyalty.base_earn_per_1000', 1) * $rate);

        if ($points <= 0) {
            return null;
        }

        return self::post($account, 'earn', $points, $reason, $source);
    }

    /** Utilisation de points (remise). */
    public static function redeem(LoyaltyAccount $account, int $points, string $reason, $source = null): LoyaltyTransaction
    {
        $points = abs($points);
        $min = (int) config('loyalty.min_redeem_points', 0);

        if ($points < $min) {
            throw ValidationException::withMessages(['points' => "Minimum {$min} points par utilisation."]);
        }
        if ($points > $account->points_balance) {
            throw ValidationException::withMessages(['points' => 'Solde de points insuffisant.']);
        }

        return self::post($account, 'redeem', -$points, $reason, $source);
    }

    /** Ajustement manuel (gain ou perte). */
    public static function adjust(LoyaltyAccount $account, int $points, string $reason, ?int $userId = null): LoyaltyTransaction
    {
        if ($points === 0) {
            throw ValidationException::withMessages(['points' => 'Indiquez un nombre de points non nul.']);
        }
        if ($points < 0 && abs($points) > $account->points_balance) {
            throw ValidationException::withMessages(['points' => 'Le retrait dépasse le solde disponible.']);
        }

        return self::post($account, 'adjust', $points, $reason, null, $userId);
    }

    public static function pointsValueFcfa(int $points): int
    {
        return $points * (int) config('loyalty.point_value_fcfa', 5);
    }

    /* ------------------------------------------------------------------ */

    private static function post(LoyaltyAccount $account, string $type, int $points, string $reason, $source = null, ?int $userId = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($account, $type, $points, $reason, $source, $userId) {
            $account->refresh();
            $account->points_balance += $points;
            if ($points > 0) {
                $account->lifetime_points += $points;
            }

            $tx = new LoyaltyTransaction([
                'type' => $type,
                'points' => $points,
                'balance_after' => $account->points_balance,
                'reason' => $reason,
                'created_by' => $userId,
            ]);
            $tx->loyalty_account_id = $account->id;
            if ($source) {
                $tx->source()->associate($source);
            }
            $tx->save();

            $account->tier_id = self::tierFor($account)?->id;
            $account->save();

            return $tx;
        });
    }

    private static function tierFor(LoyaltyAccount $account): ?LoyaltyTier
    {
        return LoyaltyTier::where('hotel_id', $account->hotel_id)
            ->where('min_points', '<=', $account->lifetime_points)
            ->orderByDesc('min_points')
            ->first();
    }

    private static function nextMemberNo(): string
    {
        $prefix = config('loyalty.member_prefix', 'KP');

        do {
            $no = $prefix.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT);
        } while (LoyaltyAccount::where('member_no', $no)->exists());

        return $no;
    }
}
