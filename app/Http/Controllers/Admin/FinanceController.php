<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\SupplierInvoice;
use App\Services\FinanceLedger;
use App\Services\PosRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function dashboard(Request $request)
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()));

        $txn = FinanceTransaction::whereBetween('operation_date', [$from->toDateString(), $to->toDateString()]);

        $income = (clone $txn)->where('direction', 'income');
        $expense = (clone $txn)->where('direction', 'expense');

        return view('admin.finance.dashboard', [
            'from' => $from, 'to' => $to,
            'accounts' => FinanceAccount::where('is_active', true)->get(),
            'incomeTotal' => (int) $income->sum('amount'),
            'expenseTotal' => (int) $expense->sum('amount'),
            'incomeByCat' => (clone $income)->selectRaw('category, sum(amount) as t')->groupBy('category')->pluck('t', 'category'),
            'expenseByCat' => (clone $expense)->selectRaw('category, sum(amount) as t')->groupBy('category')->pluck('t', 'category'),
            'receivables' => Reservation::query()
                ->whereIn('status', ['checked_in', 'checked_out'])
                ->with('guest')->get()
                ->filter(fn ($r) => $r->balance() > 0)
                ->sortByDesc(fn ($r) => $r->balance())->take(15)->values(),
            'payables' => SupplierInvoice::outstandingScope()->with('supplier')->orderBy('due_on')->take(15)->get(),
            'openSessions' => CashSession::where('status', 'open')->with('account', 'openedBy')->get(),
        ]);
    }

    public function journal(Request $request)
    {
        $filters = $request->validate([
            'direction' => ['nullable', 'in:income,expense'],
            'account' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $transactions = FinanceTransaction::query()
            ->with(['account', 'user'])
            ->when($filters['direction'] ?? null, fn ($q, $v) => $q->where('direction', $v))
            ->when($filters['account'] ?? null, fn ($q, $v) => $q->where('finance_account_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('operation_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('operation_date', '<=', $v))
            ->latest('operation_date')->latest('id')
            ->paginate(40)->withQueryString();

        return view('admin.finance.journal', [
            'transactions' => $transactions,
            'filters' => $filters,
            'accounts' => FinanceAccount::where('is_active', true)->get(),
            'incomeCategories' => config('finance.income_categories'),
            'expenseCategories' => config('finance.expense_categories'),
            'methods' => config('finance.payment_methods'),
        ]);
    }

    public function storeTransaction(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:income,expense'],
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'category' => ['required', 'string', 'max:40'],
            'method' => ['required', Rule::in(array_keys(config('finance.payment_methods')))],
            'amount' => ['required', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:150'],
            'operation_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:250'],
        ]);

        FinanceLedger::record($data);
        AuditLog::record('finance.transaction', null, ['direction' => $data['direction'], 'amount' => $data['amount']]);

        return back()->with('status', 'Opération enregistrée.');
    }

    /* ------------------------------ Caisses ------------------------- */

    public function cashSessions()
    {
        return view('admin.finance.cash', [
            'open' => CashSession::where('status', 'open')->with('account', 'openedBy')->get(),
            'recent' => CashSession::where('status', 'closed')->with('account', 'closedBy')->latest('closed_at')->take(15)->get(),
            'accounts' => FinanceAccount::where('is_active', true)->where('type', 'cash')->get(),
        ]);
    }

    public function openSession(Request $request)
    {
        $data = $request->validate([
            'finance_account_id' => ['required', 'exists:finance_accounts,id'],
            'opening_float' => ['required', 'integer', 'min:0'],
        ]);

        abort_if(
            CashSession::where('finance_account_id', $data['finance_account_id'])->where('status', 'open')->exists(),
            422, 'Une session est déjà ouverte pour cette caisse.'
        );

        CashSession::create([
            'hotel_id' => Hotel::current()->id,
            'finance_account_id' => $data['finance_account_id'],
            'opened_by' => $request->user()->id,
            'status' => 'open',
            'opening_float' => $data['opening_float'],
        ]);

        return back()->with('status', 'Caisse ouverte.');
    }

    public function closeSession(Request $request, CashSession $cashSession)
    {
        abort_unless($cashSession->status === 'open', 422);

        $data = $request->validate([
            'counted_amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:250'],
        ]);

        $session = PosRegister::close($cashSession, $request->user(), $data['counted_amount'], [], $data['note'] ?? null);

        return back()->with('status', 'Caisse clôturée. Écart : '.number_format($session->variance, 0, ',', ' ').' FCFA.');
    }

    public function receivables()
    {
        $rows = Reservation::query()
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->with('guest', 'roomCategory')
            ->get()
            ->map(fn ($r) => [
                'reservation' => $r,
                'balance' => $r->balance(),
            ])
            ->filter(fn ($x) => $x['balance'] > 0)
            ->sortByDesc('balance')
            ->values();

        return view('admin.finance.receivables', [
            'rows' => $rows,
            'total' => $rows->sum('balance'),
            'payables' => SupplierInvoice::outstandingScope()->with('supplier')->orderBy('due_on')->get(),
        ]);
    }
}
