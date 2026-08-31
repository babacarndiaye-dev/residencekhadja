<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Hotel;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Journal financier (§40) : chaque encaissement / décaissement est
 * enregistré ici et génère automatiquement l'écriture comptable (§41).
 */
class FinanceLedger
{
    /**
     * @param  array{direction:string,category:string,amount:int,label:string,method?:string,
     *   finance_account_id?:int,operation_date?:mixed,cash_session_id?:int,source?:Model,
     *   note?:string,debit_account?:string}  $attr
     */
    public static function record(array $attr): FinanceTransaction
    {
        return DB::transaction(function () use ($attr) {
            $account = isset($attr['finance_account_id'])
                ? FinanceAccount::findOrFail($attr['finance_account_id'])
                : self::accountForMethod($attr['method'] ?? 'especes');

            $session = $attr['cash_session_id'] ?? self::openSessionId($account);

            $source = $attr['source'] ?? null;

            $txn = FinanceTransaction::create([
                'hotel_id' => $account->hotel_id,
                'finance_account_id' => $account->id,
                'cash_session_id' => $session,
                'user_id' => Auth::id(),
                'direction' => $attr['direction'],
                'category' => $attr['category'],
                'method' => $attr['method'] ?? 'especes',
                'amount' => (int) $attr['amount'],
                'label' => $attr['label'],
                'operation_date' => Carbon::parse($attr['operation_date'] ?? now())->toDateString(),
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'note' => $attr['note'] ?? null,
            ]);

            $entry = self::postAccounting($txn, $account, $attr['debit_account'] ?? null);
            $txn->update(['journal_entry_id' => $entry?->id]);

            return $txn;
        });
    }

    /** Compte financier par défaut pour un moyen de paiement. */
    public static function accountForMethod(string $method): FinanceAccount
    {
        $type = match ($method) {
            'carte', 'virement', 'cheque' => 'bank',
            'mobile' => 'mobile',
            default => 'cash',
        };

        return FinanceAccount::where('hotel_id', Hotel::current()->id)
            ->where('is_active', true)
            ->where('type', $type)
            ->orderBy('id')
            ->first()
            ?? FinanceAccount::where('hotel_id', Hotel::current()->id)->orderBy('id')->firstOrFail();
    }

    private static function openSessionId(FinanceAccount $account): ?int
    {
        if ($account->type !== 'cash') {
            return null;
        }

        return CashSession::where('finance_account_id', $account->id)
            ->where('status', 'open')->latest('id')->value('id');
    }

    private static function postAccounting(FinanceTransaction $txn, FinanceAccount $account, ?string $debitOverride): JournalEntry
    {
        $treasury = config("accounting.posting.treasury.{$account->type}", '571000');

        if ($txn->direction === 'income') {
            $revenue = config("accounting.posting.income.{$txn->category}", '758000');

            return Accounting::post(
                config('accounting.posting.journal_for.income', 'VT'),
                $txn->operation_date, $txn->label,
                [
                    ['account' => $treasury, 'debit' => $txn->amount],
                    ['account' => $revenue, 'credit' => $txn->amount],
                ],
                $txn,
            );
        }

        $debitAccount = $debitOverride ?? config("accounting.posting.expense.{$txn->category}", '658000');

        return Accounting::post(
            $debitOverride ? config('accounting.posting.journal_for.supplier_payment', 'BQ') : config('accounting.posting.journal_for.expense', 'OD'),
            $txn->operation_date, $txn->label,
            [
                ['account' => $debitAccount, 'debit' => $txn->amount],
                ['account' => $treasury, 'credit' => $txn->amount],
            ],
            $txn,
        );
    }
}
