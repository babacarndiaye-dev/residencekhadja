<?php

namespace App\Services;

use App\Models\FiscalYear;
use App\Models\Hotel;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Comptabilité en partie double (§41). Les écritures sont équilibrées
 * (Σ débit = Σ crédit) et rattachées à leur pièce d'origine.
 */
class Accounting
{
    /**
     * @param  array<int,array{account:string,debit?:int,credit?:int,label?:string}>  $lines
     */
    public static function post(string $journal, $date, string $label, array $lines, ?Model $source = null): JournalEntry
    {
        $debit = (int) array_sum(array_map(fn ($l) => (int) ($l['debit'] ?? 0), $lines));
        $credit = (int) array_sum(array_map(fn ($l) => (int) ($l['credit'] ?? 0), $lines));

        if ($debit === 0 || $debit !== $credit) {
            throw new RuntimeException("Écriture déséquilibrée ({$debit} / {$credit}) : {$label}");
        }

        if (self::isLocked($date)) {
            $fy = self::fiscalYearCovering($date);
            throw new RuntimeException("Exercice « {$fy->label} » clôturé : aucune écriture ne peut être datée du ".Carbon::parse($date)->format('d/m/Y').'.');
        }

        return DB::transaction(function () use ($journal, $date, $label, $lines, $source, $debit) {
            $entry = JournalEntry::create([
                'hotel_id' => Hotel::current()->id,
                'reference' => self::reference($journal, $date),
                'journal' => $journal,
                'entry_date' => Carbon::parse($date)->toDateString(),
                'label' => $label,
                'amount' => $debit,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'created_by' => Auth::id(),
            ]);

            foreach ($lines as $l) {
                $entry->lines()->create([
                    'ledger_account_id' => self::account($l['account'])->id,
                    'label' => $l['label'] ?? $label,
                    'debit' => (int) ($l['debit'] ?? 0),
                    'credit' => (int) ($l['credit'] ?? 0),
                ]);
            }

            return $entry;
        });
    }

    public static function account(string $code): LedgerAccount
    {
        return LedgerAccount::firstOrCreate(
            ['hotel_id' => Hotel::current()->id, 'code' => $code],
            [
                'name' => config("accounting.chart.$code.0", $code),
                'type' => config("accounting.chart.$code.1", 'asset'),
            ],
        );
    }

    public static function reference(string $journal, $date): string
    {
        $prefix = $journal.'-'.Carbon::parse($date)->format('Ym').'-';
        $seq = JournalEntry::where('reference', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(2));
    }

    /**
     * Balance générale sur une période.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public static function trialBalance($from, $to)
    {
        return LedgerAccount::query()
            ->where('ledger_accounts.hotel_id', Hotel::current()->id)
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.ledger_account_id', '=', 'ledger_accounts.id')
            ->leftJoin('journal_entries', function ($j) use ($from, $to) {
                $j->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->whereDate('journal_entries.entry_date', '>=', $from)
                    ->whereDate('journal_entries.entry_date', '<=', $to);
            })
            ->groupBy('ledger_accounts.id', 'ledger_accounts.code', 'ledger_accounts.name', 'ledger_accounts.type')
            ->orderBy('ledger_accounts.code')
            ->selectRaw('ledger_accounts.code, ledger_accounts.name, ledger_accounts.type,
                coalesce(sum(journal_entry_lines.debit),0) as debit,
                coalesce(sum(journal_entry_lines.credit),0) as credit')
            ->get()
            ->map(fn ($r) => [
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'debit' => (int) $r->debit,
                'credit' => (int) $r->credit,
                'balance' => (int) $r->debit - (int) $r->credit,
            ]);
    }

    /** Grand livre d'un compte. */
    public static function ledger(LedgerAccount $account, $from, $to)
    {
        return JournalEntryLine::query()
            ->where('ledger_account_id', $account->id)
            ->whereHas('entry', fn ($q) => $q
                ->whereDate('entry_date', '>=', $from)
                ->whereDate('entry_date', '<=', $to))
            ->with('entry')
            ->get()
            ->sortBy(fn ($l) => $l->entry->entry_date)
            ->values();
    }

    /* ==================================================================
     |  Exercices comptables & verrouillage
     |================================================================== */

    /** L'exercice qui couvre la date du jour (créé s'il n'existe pas). */
    public static function currentFiscalYear(): FiscalYear
    {
        return self::fiscalYearFor(Carbon::today());
    }

    /** L'exercice couvrant une date (calendaire : 1er janvier → 31 décembre). */
    public static function fiscalYearFor($date): FiscalYear
    {
        $d = Carbon::parse($date);

        return self::fiscalYearCovering($d) ?? FiscalYear::create([
            'hotel_id' => Hotel::current()->id,
            'label' => (string) $d->year,
            'starts_on' => $d->copy()->startOfYear()->toDateString(),
            'ends_on' => $d->copy()->endOfYear()->toDateString(),
            'status' => 'open',
        ]);
    }

    public static function fiscalYearCovering($date): ?FiscalYear
    {
        $d = Carbon::parse($date)->toDateString();

        return FiscalYear::where('hotel_id', Hotel::current()->id)
            ->whereDate('starts_on', '<=', $d)
            ->whereDate('ends_on', '>=', $d)
            ->first();
    }

    /** Vrai si la date tombe dans un exercice clôturé. */
    public static function isLocked($date): bool
    {
        return self::fiscalYearCovering($date)?->isClosed() ?? false;
    }

    /**
     * Clôture d'un exercice : regroupe les comptes de gestion (produits /
     * charges) dans le compte de résultat, fige le résultat et verrouille
     * la période. Les comptes de bilan sont reportés naturellement (grand
     * livre cumulatif) — pas d'écriture d'à-nouveaux.
     */
    public static function closeFiscalYear(FiscalYear $fy, ?int $userId = null): FiscalYear
    {
        if ($fy->isClosed()) {
            throw new RuntimeException("L'exercice « {$fy->label} » est déjà clôturé.");
        }

        return DB::transaction(function () use ($fy, $userId) {
            $balances = self::pnlBalances($fy->starts_on->toDateString(), $fy->ends_on->toDateString());

            $lines = [];
            $result = 0; // produits - charges

            foreach ($balances as $row) {
                if ($row['type'] === 'income') {
                    $bal = $row['credit'] - $row['debit']; // solde créditeur
                    if ($bal !== 0) {
                        $lines[] = ['account' => $row['code'], 'debit' => max($bal, 0), 'credit' => max(-$bal, 0), 'label' => 'Solde '.$row['name']];
                    }
                    $result += $bal;
                } else { // expense
                    $bal = $row['debit'] - $row['credit']; // solde débiteur
                    if ($bal !== 0) {
                        $lines[] = ['account' => $row['code'], 'debit' => max(-$bal, 0), 'credit' => max($bal, 0), 'label' => 'Solde '.$row['name']];
                    }
                    $result -= $bal;
                }
            }

            $entry = null;
            if ($lines) {
                // Contrepartie : compte de résultat (crédit si bénéfice, débit si perte).
                $lines[] = [
                    'account' => config('accounting.result_account'),
                    'debit' => max(-$result, 0),
                    'credit' => max($result, 0),
                    'label' => $result >= 0 ? 'Bénéfice de l’exercice' : 'Perte de l’exercice',
                ];

                $entry = self::post(
                    config('accounting.closing_journal', 'CL'),
                    $fy->ends_on->toDateString(),
                    "Clôture de l'exercice {$fy->label}",
                    $lines,
                    $fy,
                );
            }

            $fy->update([
                'status' => 'closed',
                'result_amount' => $result,
                'closing_entry_id' => $entry?->id,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            return $fy->refresh();
        });
    }

    /** Ré-ouvre un exercice clôturé (supprime l'écriture de clôture). */
    public static function reopenFiscalYear(FiscalYear $fy): FiscalYear
    {
        if (! $fy->isClosed()) {
            throw new RuntimeException("L'exercice « {$fy->label} » n'est pas clôturé.");
        }

        return DB::transaction(function () use ($fy) {
            $fy->closingEntry?->delete();
            $fy->update([
                'status' => 'open',
                'result_amount' => null,
                'closing_entry_id' => null,
                'closed_by' => null,
                'closed_at' => null,
            ]);

            return $fy->refresh();
        });
    }

    /** Contre-passation d'une écriture (miroir débit/crédit, journal OD). */
    public static function reverse(JournalEntry $entry, ?int $userId = null): JournalEntry
    {
        $date = Carbon::today();
        if (self::isLocked($date)) {
            $date = self::currentFiscalYear()->ends_on->copy()->addDay();
            $date = self::fiscalYearFor($date)->starts_on;
        }

        $lines = $entry->lines->map(fn ($l) => [
            'account' => $l->account->code,
            'debit' => (int) $l->credit,
            'credit' => (int) $l->debit,
            'label' => 'Extourne — '.($l->label ?? $entry->label),
        ])->all();

        return self::post('OD', $date, "Contre-passation de {$entry->reference} — {$entry->label}", $lines, $entry);
    }

    /* ==================================================================
     |  États de synthèse
     |================================================================== */

    /** Compte de résultat sur une période (hors écritures de clôture). */
    public static function incomeStatement($from, $to): array
    {
        $rows = self::pnlBalances($from, $to);

        $income = $rows->where('type', 'income')
            ->map(fn ($r) => $r + ['amount' => $r['credit'] - $r['debit']])
            ->filter(fn ($r) => $r['amount'] !== 0)->values();
        $expense = $rows->where('type', 'expense')
            ->map(fn ($r) => $r + ['amount' => $r['debit'] - $r['credit']])
            ->filter(fn ($r) => $r['amount'] !== 0)->values();

        $totalIncome = (int) $income->sum('amount');
        $totalExpense = (int) $expense->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'result' => $totalIncome - $totalExpense,
        ];
    }

    /** Bilan à une date (soldes cumulés des comptes de bilan). */
    public static function balanceSheet($asOf): array
    {
        $to = Carbon::parse($asOf);
        $rows = self::trialBalance('1900-01-01', $to->toDateString());

        $assets = $rows->where('type', 'asset')
            ->map(fn ($r) => $r + ['amount' => $r['balance']])
            ->filter(fn ($r) => $r['amount'] !== 0)->values();
        $liabilities = $rows->whereIn('type', ['liability', 'equity'])
            ->map(fn ($r) => $r + ['amount' => -$r['balance']]) // solde créditeur
            ->filter(fn ($r) => $r['amount'] !== 0)->values();

        // Résultat de l'exercice, non encore affecté. S'il existe un exercice
        // clôturé sur cette date, le résultat est déjà dans le compte 120000 →
        // on ne l'ajoute pas ; sinon (exercice ouvert ou inexistant) on le calcule
        // depuis le début de l'année civile de la date.
        $fy = self::fiscalYearCovering($to);
        $pendingResult = 0;
        if (! $fy || $fy->isOpen()) {
            $start = $fy?->starts_on->toDateString() ?? $to->copy()->startOfYear()->toDateString();
            $pendingResult = self::incomeStatement($start, $to->toDateString())['result'];
        }

        $totalAssets = (int) $assets->sum('amount');
        $totalLiabilities = (int) $liabilities->sum('amount') + $pendingResult;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'pending_result' => $pendingResult,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'balanced' => $totalAssets === $totalLiabilities,
        ];
    }

    /** Déclaration de TVA sur une période. */
    public static function vatReturn($from, $to): array
    {
        $acc = config('accounting.posting.accounts');
        $movement = fn (string $code, bool $creditNature) => self::accountMovement($code, $from, $to, $creditNature);

        $collected = $movement($acc['vat_collected'], true);   // 443100, solde créditeur
        $deductible = $movement($acc['vat_deductible'], false); // 445100, solde débiteur
        $net = $collected - $deductible;

        return [
            'collected' => $collected,
            'deductible' => $deductible,
            'net' => $net,
            'due' => max($net, 0),
            'credit' => max(-$net, 0),
        ];
    }

    /** Grand livre général : tous les comptes mouvementés sur la période. */
    public static function generalLedger($from, $to): Collection
    {
        return LedgerAccount::where('hotel_id', Hotel::current()->id)
            ->orderBy('code')
            ->get()
            ->map(function (LedgerAccount $account) use ($from, $to) {
                $opening = (int) JournalEntryLine::where('ledger_account_id', $account->id)
                    ->whereHas('entry', fn ($q) => $q->whereDate('entry_date', '<', $from))
                    ->sum(DB::raw('debit - credit'));

                $lines = self::ledger($account, $from, $to);

                return [
                    'account' => $account,
                    'opening' => $opening,
                    'debit' => (int) $lines->sum('debit'),
                    'credit' => (int) $lines->sum('credit'),
                    'closing' => $opening + (int) $lines->sum('debit') - (int) $lines->sum('credit'),
                    'lines' => $lines,
                ];
            })
            ->filter(fn ($r) => $r['lines']->isNotEmpty() || $r['opening'] !== 0)
            ->values();
    }

    /* ------------------------------------------------------------------ */

    /** Soldes des comptes de gestion (produits + charges) sur une période, hors journal de clôture. */
    private static function pnlBalances($from, $to): Collection
    {
        $closing = config('accounting.closing_journal', 'CL');

        return LedgerAccount::query()
            ->where('hotel_id', Hotel::current()->id)
            ->whereIn('type', ['income', 'expense'])
            ->orderBy('code')
            ->get()
            ->map(function (LedgerAccount $account) use ($from, $to, $closing) {
                $agg = JournalEntryLine::where('ledger_account_id', $account->id)
                    ->whereHas('entry', fn ($q) => $q
                        ->whereDate('entry_date', '>=', $from)
                        ->whereDate('entry_date', '<=', $to)
                        ->where('journal', '!=', $closing))
                    ->selectRaw('coalesce(sum(debit),0) as d, coalesce(sum(credit),0) as c')
                    ->first();

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => (int) $agg->d,
                    'credit' => (int) $agg->c,
                ];
            });
    }

    /** Mouvement net d'un compte sur une période (créditeur ou débiteur selon la nature). */
    private static function accountMovement(string $code, $from, $to, bool $creditNature): int
    {
        $account = LedgerAccount::where('hotel_id', Hotel::current()->id)->where('code', $code)->first();
        if (! $account) {
            return 0;
        }

        $lines = self::ledger($account, $from, $to);
        $debit = (int) $lines->sum('debit');
        $credit = (int) $lines->sum('credit');

        return $creditNature ? $credit - $debit : $debit - $credit;
    }
}
