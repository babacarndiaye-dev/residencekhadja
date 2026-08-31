<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\Accounting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingController extends Controller
{
    public function index()
    {
        return view('admin.accounting.index', [
            'accounts' => LedgerAccount::orderBy('code')->get()->groupBy('type'),
            'types' => LedgerAccount::TYPES,
            'journals' => config('accounting.journals'),
            'entryCount' => JournalEntry::count(),
            'fiscalYear' => Accounting::currentFiscalYear(),
        ]);
    }

    /** Bornes de période : défaut = exercice courant. */
    private function range(Request $request): array
    {
        $fy = Accounting::currentFiscalYear();

        return [
            Carbon::parse($request->query('from', $fy->starts_on)),
            Carbon::parse($request->query('to', min(now(), $fy->ends_on))),
        ];
    }

    public function incomeStatement(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('admin.accounting.income-statement', [
            'from' => $from, 'to' => $to,
            'report' => Accounting::incomeStatement($from->toDateString(), $to->toDateString()),
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $asOf = Carbon::parse($request->query('as_of', min(now(), Accounting::currentFiscalYear()->ends_on)));

        return view('admin.accounting.balance-sheet', [
            'asOf' => $asOf,
            'sheet' => Accounting::balanceSheet($asOf->toDateString()),
        ]);
    }

    public function vat(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('admin.accounting.vat', [
            'from' => $from, 'to' => $to,
            'report' => Accounting::vatReturn($from->toDateString(), $to->toDateString()),
        ]);
    }

    public function generalLedger(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('admin.accounting.general-ledger', [
            'from' => $from, 'to' => $to,
            'accounts' => Accounting::generalLedger($from->toDateString(), $to->toDateString()),
        ]);
    }

    public function fiscalYears()
    {
        return view('admin.accounting.fiscal-years', [
            'years' => FiscalYear::orderByDesc('starts_on')->with('closedBy')->get(),
            'current' => Accounting::currentFiscalYear(),
        ]);
    }

    public function closeFiscalYear(FiscalYear $fiscalYear, Request $request)
    {
        try {
            $fy = Accounting::closeFiscalYear($fiscalYear, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['fiscal_year' => $e->getMessage()]);
        }

        AuditLog::record('accounting.fiscal_year_closed', $fy, ['result' => $fy->result_amount]);

        return back()->with('status', "Exercice {$fy->label} clôturé — résultat ".money($fy->result_amount).'.');
    }

    public function reopenFiscalYear(FiscalYear $fiscalYear)
    {
        try {
            Accounting::reopenFiscalYear($fiscalYear);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['fiscal_year' => $e->getMessage()]);
        }

        AuditLog::record('accounting.fiscal_year_reopened', $fiscalYear);

        return back()->with('status', "Exercice {$fiscalYear->label} ré-ouvert.");
    }

    public function reverseEntry(JournalEntry $entry, Request $request)
    {
        try {
            $reversal = Accounting::reverse($entry->load('lines.account'), $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['entry' => $e->getMessage()]);
        }

        AuditLog::record('accounting.entry_reversed', $entry, ['reversal' => $reversal->reference]);

        return back()->with('status', "Écriture {$entry->reference} contre-passée ({$reversal->reference}).");
    }

    public function entries(Request $request)
    {
        $filters = $request->validate([
            'journal' => ['nullable', 'string', 'max:8'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $entries = JournalEntry::query()
            ->with(['lines.account', 'creator'])
            ->when($filters['journal'] ?? null, fn ($q, $v) => $q->where('journal', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('entry_date', '<=', $v))
            ->latest('entry_date')->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.accounting.entries', [
            'entries' => $entries,
            'filters' => $filters,
            'journals' => config('accounting.journals'),
            'accounts' => LedgerAccount::orderBy('code')->get(),
        ]);
    }

    public function storeEntry(Request $request)
    {
        $data = $request->validate([
            'journal' => ['required', 'in:'.implode(',', array_keys(config('accounting.journals')))],
            'entry_date' => ['required', 'date'],
            'label' => ['required', 'string', 'max:150'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.code' => ['required', 'exists:ledger_accounts,code'],
            'lines.*.debit' => ['nullable', 'integer', 'min:0'],
            'lines.*.credit' => ['nullable', 'integer', 'min:0'],
        ]);

        $lines = collect($data['lines'])
            ->map(fn ($l) => ['account' => $l['code'], 'debit' => (int) ($l['debit'] ?? 0), 'credit' => (int) ($l['credit'] ?? 0)])
            ->filter(fn ($l) => $l['debit'] > 0 || $l['credit'] > 0)
            ->values()->all();

        try {
            Accounting::post($data['journal'], $data['entry_date'], $data['label'], $lines);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return back()->with('status', 'Écriture enregistrée.');
    }

    public function balance(Request $request)
    {
        $from = Carbon::parse($request->query('from', now()->startOfYear()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()));

        $rows = Accounting::trialBalance($from->toDateString(), $to->toDateString());

        return view('admin.accounting.balance', [
            'from' => $from, 'to' => $to,
            'rows' => $rows,
            'totalDebit' => $rows->sum('debit'),
            'totalCredit' => $rows->sum('credit'),
        ]);
    }

    public function ledger(Request $request, LedgerAccount $account)
    {
        $from = Carbon::parse($request->query('from', now()->startOfYear()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()));

        $lines = Accounting::ledger($account, $from->toDateString(), $to->toDateString());

        $running = 0;
        $rows = $lines->map(function ($l) use (&$running) {
            $running += $l->debit - $l->credit;

            return ['line' => $l, 'running' => $running];
        });

        return view('admin.accounting.ledger', [
            'account' => $account,
            'from' => $from, 'to' => $to,
            'rows' => $rows,
            'accounts' => LedgerAccount::orderBy('code')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->query('from', now()->startOfYear()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()));

        $entries = JournalEntry::with('lines.account')
            ->whereDate('entry_date', '>=', $from->toDateString())
            ->whereDate('entry_date', '<=', $to->toDateString())
            ->orderBy('entry_date')->orderBy('id')->get();

        $filename = 'ecritures_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Journal', 'Pièce', 'Libellé', 'Compte', 'Intitulé', 'Débit', 'Crédit'], ';');
            foreach ($entries as $e) {
                foreach ($e->lines as $l) {
                    fputcsv($out, [
                        $e->entry_date->format('d/m/Y'), $e->journal, $e->reference, $e->label,
                        $l->account->code, $l->account->name, $l->debit, $l->credit,
                    ], ';');
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
