<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Session de caisse du comptoir : ouverture avec fond + détail des coupures,
 * clôture avec comptage → écart comptabilisé en OD. S'appuie sur `cash_sessions`
 * (module finance) ; `FinanceLedger::record()` rattache déjà chaque encaissement
 * espèces à la session ouverte du compte.
 */
class PosRegister
{
    /** Session ouverte pour un compte de caisse, s'il y en a une. */
    public static function openFor(FinanceAccount $account): ?CashSession
    {
        return CashSession::where('finance_account_id', $account->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<int|string,int>  $denoms  coupure => quantité
     */
    public static function open(FinanceAccount $account, User $user, int $float, array $denoms = []): CashSession
    {
        abort_if(
            CashSession::where('finance_account_id', $account->id)->where('status', 'open')->exists(),
            422,
            'Une session est déjà ouverte pour cette caisse.'
        );

        return CashSession::create([
            'hotel_id' => $account->hotel_id,
            'finance_account_id' => $account->id,
            'opened_by' => $user->id,
            'status' => 'open',
            'opening_float' => $float,
            'opening_denominations' => $denoms ?: null,
        ]);
    }

    /**
     * Clôture : comptage vs théorique → écart en OD (658000 perte / 758000
     * excédent) contre la trésorerie. Utilisé par le POS et par le module finance.
     *
     * @param  array<int|string,int>  $denoms
     */
    public static function close(CashSession $session, User $user, int $counted, array $denoms = [], ?string $note = null): CashSession
    {
        abort_unless($session->status === 'open', 422, 'Session déjà clôturée.');

        $expected = $session->expected();
        $variance = $counted - $expected;

        $session->update([
            'status' => 'closed',
            'closed_by' => $user->id,
            'counted_amount' => $counted,
            'closing_denominations' => $denoms ?: null,
            'expected_amount' => $expected,
            'variance' => $variance,
            'note' => $note,
            'closed_at' => now(),
        ]);

        if ($variance !== 0) {
            $treasury = config('accounting.posting.treasury.cash', '571000');
            if ($variance < 0) {
                Accounting::post('OD', now(), "Écart de caisse {$session->account->name}", [
                    ['account' => '658000', 'debit' => abs($variance)],
                    ['account' => $treasury, 'credit' => abs($variance)],
                ], $session);
            } else {
                Accounting::post('OD', now(), "Excédent de caisse {$session->account->name}", [
                    ['account' => $treasury, 'debit' => $variance],
                    ['account' => '758000', 'credit' => $variance],
                ], $session);
            }
        }

        AuditLog::record('finance.cash.closed', $session, ['variance' => $variance]);

        return $session->fresh();
    }

    /** Somme d'un décompte de coupures {valeur => quantité}. */
    public static function denomsTotal(array $denoms): int
    {
        $total = 0;
        foreach ($denoms as $value => $count) {
            $total += (int) $value * (int) $count;
        }

        return $total;
    }

    /** Un compte manager actif dont le PIN caisse correspond, sinon null. */
    public static function authorizeManager(string $pin): ?User
    {
        $pin = trim($pin);
        if ($pin === '') {
            return null;
        }

        $managers = User::query()
            ->where('is_active', true)
            ->whereIn('role', config('pos.manager_roles', ['direction', 'admin']))
            ->whereNotNull('pos_pin')
            ->get();

        foreach ($managers as $manager) {
            if (Hash::check($pin, $manager->pos_pin)) {
                return $manager;
            }
        }

        return null;
    }
}
