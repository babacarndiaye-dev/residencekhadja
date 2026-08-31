<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PosInvoice;
use App\Models\AuditLog;
use App\Models\BreakfastCheckin;
use App\Models\CashSession;
use App\Models\FinanceAccount;
use App\Models\Hotel;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\QrLocation;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Venue;
use App\Services\Analytics;
use App\Services\CartPricer;
use App\Services\FinanceLedger;
use App\Services\PosRegister;
use App\Services\RecipeConsumption;
use App\Services\Sms;
use App\Support\GuestFolio;
use App\Support\Sequence;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    /** Moyens de paiement du comptoir → moyen trésorerie interne. */
    private const METHOD_MAP = [
        'especes' => 'especes',
        'carte' => 'carte',
        'wave' => 'mobile',
        'orange_money' => 'mobile',
        'free_money' => 'mobile',
    ];

    public const SALE_TYPES = [
        'restaurant' => 'Restaurant',
        'bar' => 'Bar',
        'pool' => 'Piscine',
        'room_service' => 'Room service',
    ];

    /* ============================ Tableau de bord ============================ */

    public function index(Request $request)
    {
        $account = $this->cashAccount();
        $session = $account ? PosRegister::openFor($account) : null;
        $today = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];

        return view('admin.pos.dashboard', [
            'account' => $account,
            'session' => $session?->loadCount('orders'),
            'cashAccounts' => FinanceAccount::where('is_active', true)->where('type', 'cash')->get(),
            'denominations' => config('pos.denominations'),
            'fnb' => Analytics::fnb(...$today),
            'openOrders' => Order::where('source', 'pos')->open()->count(),
            'heldOrders' => Order::where('source', 'pos')->where('status', 'held')->count(),
            'roomServicePending' => Order::where('sale_type', 'room_service')->open()->count(),
            'needsPin' => ! $request->user()->hasPosPin() && $request->user()->hasRole(...config('pos.manager_roles')),
            // Identification légère : opérateur + poste en session.
            'operator' => $request->session()->get('pos.operator_name'),
            'station' => $request->session()->get('pos.station'),
            'stations' => config('pos.stations'),
            'posUsers' => User::where('is_active', true)->whereIn('role', config('pos.server_roles'))
                ->whereNotNull('pos_pin')->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    /** Prise de poste : opérateur (PIN) + poste, stockés en session. */
    public function storeStation(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where('is_active', true)],
            'pin' => ['required', 'string', 'max:12'],
            'station' => ['required', Rule::in(array_keys(config('pos.stations')))],
        ]);

        $user = User::findOrFail($data['user_id']);
        abort_unless($user->pos_pin && Hash::check(trim($data['pin']), $user->pos_pin), 422, 'PIN incorrect.');

        $request->session()->put([
            'pos.operator_id' => $user->id,
            'pos.operator_name' => $user->name,
            'pos.station' => $data['station'],
        ]);

        return redirect()->route('admin.pos.index')->with('status', "Poste pris : {$user->name} — ".config("pos.stations.{$data['station']}"));
    }

    public function exitStation(Request $request)
    {
        $request->session()->forget(['pos.operator_id', 'pos.operator_name', 'pos.station']);

        return redirect()->route('admin.pos.index')->with('status', 'Poste libéré.');
    }

    /* ============================== Reporting POS ============================== */

    public function reports(Request $request)
    {
        $period = $request->query('period', '30d');
        [$from, $to] = Analytics::resolveRange($period, $request->query('from'), $request->query('to'));

        return view('admin.pos.reports', [
            'from' => $from, 'to' => $to, 'period' => $period,
            'sales' => Analytics::posSales($from, $to),
            'bestsellers' => Analytics::posBestsellers($from, $to),
            'servers' => Analytics::posServers($from, $to),
            'saleTypes' => self::SALE_TYPES,
        ]);
    }

    /* ============================== Écran de vente ============================== */

    public function register(Request $request)
    {
        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $venue = $request->filled('venue')
            ? $venues->firstWhere('id', $request->integer('venue'))
            : $venues->firstWhere('is_room_service', false) ?? $venues->first();

        abort_unless($venue, 404, 'Aucun point de restauration configuré.');

        $account = $this->cashAccount();
        $resumeOrder = $request->filled('resume')
            ? Order::where('source', 'pos')->where('status', 'held')->with('items')->find($request->integer('resume'))
            : null;

        return view('admin.pos.register', [
            'venue' => $venue,
            'venues' => $venues,
            'menu' => $this->menuTree($venue),
            'saleTypes' => self::SALE_TYPES,
            'servers' => User::where('is_active', true)
                ->whereIn('role', config('pos.server_roles'))
                ->orderBy('name')->get(['id', 'name']),
            'discountLimits' => config('pos.discount'),
            'session' => $account ? PosRegister::openFor($account) : null,
            'preServerId' => $request->session()->get('pos.operator_id'),
            'preSaleType' => $request->session()->get('pos.station'),
            'operator' => $request->session()->get('pos.operator_name'),
            'tables' => QrLocation::tables()->where('venue_id', $venue->id)
                ->orderByRaw('zone is null')->orderBy('zone')->orderBy('label')->get()
                ->map(fn (QrLocation $t) => [
                    'id' => $t->id, 'label' => $t->label, 'zone' => $t->zone,
                    'status' => $t->occupancyStatus(),
                ])->values(),
            'preTableId' => $request->integer('table') ?: null,
            'resume' => $resumeOrder ? $this->heldPayload($resumeOrder) : null,
            'rooms' => Reservation::where('status', 'checked_in')->with('guest', 'room')->get()
                ->map(fn (Reservation $r) => [
                    'id' => $r->id,
                    'room' => $r->room->number ?? '—',
                    'guest' => $r->guest->fullName(),
                ])->values(),
            'held' => Order::where('source', 'pos')->where('status', 'held')
                ->with('items')->latest()->get()
                ->map(fn (Order $o) => $this->heldPayload($o)),
        ]);
    }

    /** @return array<string,mixed> */
    private function heldPayload(Order $o): array
    {
        return [
            'id' => $o->id,
            'reference' => $o->reference,
            'label' => $o->table_label ?: $o->guest_name,
            'table_id' => $o->qr_location_id,
            'total' => $o->total,
            'count' => $o->items->sum('quantity'),
            'lines' => $o->items->map(fn ($it) => [
                'item_id' => $it->menu_item_id, 'name' => $it->name, 'quantity' => $it->quantity,
                'unit_price' => $it->unit_price, 'options' => $it->options ?? [],
            ]),
        ];
    }

    public function checkout(Request $request, CartPricer $pricer)
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'sale_type' => ['required', Rule::in(array_keys(self::SALE_TYPES))],
            'table_label' => ['nullable', 'string', 'max:40'],
            'table_id' => ['nullable', Rule::exists('qr_locations', 'id')->where('type', 'table')],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'server_id' => ['nullable', Rule::exists('users', 'id')],
            'discount' => ['nullable', 'array'],
            'discount.type' => ['nullable', 'in:amount,percent'],
            'discount.value' => ['nullable', 'integer', 'min:0'],
            'resume_order_id' => ['nullable', 'exists:orders,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'lines.*.options' => ['nullable', 'array'],
            'lines.*.note' => ['nullable', 'string', 'max:180'],
            'payments' => ['array'],
            'payments.*.method' => ['required', Rule::in([...array_keys(self::METHOD_MAP), 'chambre'])],
            'payments.*.amount' => ['required', 'integer', 'min:1'],
            'payments.*.tendered' => ['nullable', 'integer', 'min:0'],
            'payments.*.reservation_id' => ['nullable', Rule::exists('reservations', 'id')->where('status', 'checked_in')],
            'manager_pin' => ['nullable', 'string', 'max:12'],
        ]);

        $venue = Venue::findOrFail($data['venue_id']);
        $table = ! empty($data['table_id']) ? QrLocation::find($data['table_id']) : null;
        $discountSpec = isset($data['discount']['value'])
            ? ['type' => $data['discount']['type'] ?? 'amount', 'value' => (int) $data['discount']['value']]
            : 0;

        $quote = $pricer->priceForVenue($venue, $data['lines'], $discountSpec);
        $needsKitchen = collect($quote['lines'])->contains(fn ($l) => $l['needs_kitchen']);

        // Autorisation manager si la remise dépasse un seuil.
        $manager = null;
        if ($this->discountNeedsAuthorization($quote)) {
            $manager = $this->requireManager($data['manager_pin'] ?? null, 'cette remise');
        }

        $payments = $data['payments'] ?? [];

        // Contrôle des règlements : la part chambre exige une réservation en séjour.
        foreach ($payments as $p) {
            if ($p['method'] === 'chambre' && empty($p['reservation_id'])) {
                throw ValidationException::withMessages(['payments' => 'Sélectionnez la chambre à imputer.']);
            }
        }
        $paid = collect($payments)->sum('amount');
        if ($quote['total'] > 0 && $paid < $quote['total']) {
            throw ValidationException::withMessages(['payments' => 'Le règlement ne couvre pas le total.']);
        }

        $account = $this->cashAccount();
        $session = $account ? PosRegister::openFor($account) : null;

        $order = DB::transaction(function () use ($data, $payments, $venue, $table, $quote, $request, $needsKitchen, $session, $manager) {
            $resumeId = $data['resume_order_id'] ?? null;
            $order = $resumeId
                ? tap(Order::findOrFail($resumeId), fn (Order $o) => $o->items()->delete())
                : new Order(['reference' => $this->reference(), 'hotel_id' => $venue->hotel_id]);

            $methods = collect($payments)->pluck('method')->unique();

            $order->fill([
                'source' => 'pos',
                'venue_id' => $venue->id,
                'type' => $data['sale_type'] === 'room_service' ? 'room_service' : 'dine_in',
                'sale_type' => $data['sale_type'],
                'status' => $needsKitchen ? 'new' : 'served',
                'served_at' => $needsKitchen ? null : now(),
                'guest_name' => $data['customer_name'] ?? null,
                'qr_location_id' => $table?->id,
                'table_label' => $table?->label ?? ($data['table_label'] ?? null),
                'cashier_id' => $request->user()->id,
                'server_id' => $data['server_id'] ?? null,
                'cash_session_id' => $session?->id,
                'subtotal' => $quote['subtotal'],
                'service_charge' => $quote['service_charge'],
                'discount' => $quote['discount'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'payment_method' => $methods->count() > 1 ? 'mixte' : $methods->first(),
                'placed_at' => now(),
            ])->save();

            foreach ($quote['lines'] as $line) {
                $order->items()->create([
                    'menu_item_id' => $line['menu_item_id'],
                    'name' => $line['name'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'options' => $line['options'],
                    'note' => $line['note'],
                    'line_total' => $line['line_total'],
                    'needs_kitchen' => $line['needs_kitchen'],
                ]);
            }

            $this->settlePayments($order, $payments, $session, $manager, $request->user());
            RecipeConsumption::applyForOrder($order);

            return $order;
        });

        AuditLog::record('pos.sale', $order, [
            'method' => $order->payment_method,
            'total' => $order->total,
            'discount' => $order->discount,
            'authorized_by' => $manager?->id,
        ]);

        $cashLine = collect($payments)->firstWhere('method', 'especes');

        return response()->json([
            'ok' => true,
            'reference' => $order->reference,
            'total' => $order->total,
            'change' => $cashLine && ! empty($cashLine['tendered'])
                ? max(0, (int) $cashLine['tendered'] - (int) $cashLine['amount'])
                : max(0, $paid - $quote['total']),
            'receipt_url' => route('admin.pos.receipt', $order),
        ]);
    }

    public function hold(Request $request, CartPricer $pricer)
    {
        $data = $request->validate([
            'venue_id' => ['required', 'exists:venues,id'],
            'sale_type' => ['required', Rule::in(array_keys(self::SALE_TYPES))],
            'table_label' => ['nullable', 'string', 'max:40'],
            'table_id' => ['nullable', Rule::exists('qr_locations', 'id')->where('type', 'table')],
            'customer_name' => ['nullable', 'string', 'max:80'],
            'server_id' => ['nullable', Rule::exists('users', 'id')],
            'discount' => ['nullable', 'array'],
            'discount.type' => ['nullable', 'in:amount,percent'],
            'discount.value' => ['nullable', 'integer', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'lines.*.options' => ['nullable', 'array'],
        ]);

        $venue = Venue::findOrFail($data['venue_id']);
        $table = ! empty($data['table_id']) ? QrLocation::find($data['table_id']) : null;
        $discountSpec = isset($data['discount']['value'])
            ? ['type' => $data['discount']['type'] ?? 'amount', 'value' => (int) $data['discount']['value']]
            : 0;
        $quote = $pricer->priceForVenue($venue, $data['lines'], $discountSpec);

        $order = DB::transaction(function () use ($data, $venue, $table, $quote, $request) {
            $order = Order::create([
                'reference' => $this->reference(),
                'hotel_id' => $venue->hotel_id,
                'source' => 'pos',
                'venue_id' => $venue->id,
                'type' => $data['sale_type'] === 'room_service' ? 'room_service' : 'dine_in',
                'sale_type' => $data['sale_type'],
                'status' => 'held',
                'payment_status' => 'unpaid',
                'guest_name' => $data['customer_name'] ?? null,
                'qr_location_id' => $table?->id,
                'table_label' => $table?->label ?? ($data['table_label'] ?? null),
                'cashier_id' => $request->user()->id,
                'server_id' => $data['server_id'] ?? null,
                'subtotal' => $quote['subtotal'],
                'service_charge' => $quote['service_charge'],
                'discount' => $quote['discount'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
            ]);

            foreach ($quote['lines'] as $line) {
                $order->items()->create([
                    'menu_item_id' => $line['menu_item_id'], 'name' => $line['name'],
                    'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'],
                    'options' => $line['options'], 'note' => $line['note'], 'line_total' => $line['line_total'],
                    'needs_kitchen' => $line['needs_kitchen'],
                ]);
            }

            return $order;
        });

        return response()->json(['ok' => true, 'reference' => $order->reference]);
    }

    public function void(Request $request, Order $order)
    {
        abort_unless($order->source === 'pos' && ! $order->isPaid(), 422);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:180'],
            'manager_pin' => ['nullable', 'string', 'max:12'],
        ]);

        $manager = $this->requireManager($data['manager_pin'] ?? null, 'une annulation');

        $order->update(['status' => 'cancelled']);
        AuditLog::record('pos.void', $order, ['reason' => $data['reason'], 'authorized_by' => $manager->id]);

        return response()->json(['ok' => true]);
    }

    public function refund(Request $request, Order $order)
    {
        abort_unless($order->source === 'pos' && $order->isPaid() && ! $order->isRefunded(), 422, 'Remboursement impossible.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:180'],
            'method' => ['nullable', Rule::in(array_keys(self::METHOD_MAP))],
            'amount' => ['nullable', 'integer', 'min:1'],
            'manager_pin' => ['nullable', 'string', 'max:12'],
        ]);

        $manager = $this->requireManager($data['manager_pin'] ?? null, 'un remboursement');
        $method = $data['method'] ?? 'especes';
        $amount = min((int) ($data['amount'] ?? $order->total), max(1, $order->amountPaid()));

        $account = $this->cashAccount();
        $session = $account ? PosRegister::openFor($account) : null;

        DB::transaction(function () use ($order, $amount, $method, $data, $manager, $session, $request) {
            $order->payments()->create([
                'method' => $method,
                'amount' => -$amount,
                'cash_session_id' => $method === 'especes' ? $session?->id : null,
                'recorded_by' => $request->user()->id,
                'authorized_by' => $manager->id,
            ]);

            if ($order->total > 0 && Schema::hasTable('finance_accounts') && FinanceAccount::query()->exists()) {
                FinanceLedger::record([
                    'direction' => 'expense',
                    'category' => config('pos.refund.expense_category', 'divers_charges'),
                    'method' => self::METHOD_MAP[$method] ?? 'especes',
                    'amount' => $amount,
                    'label' => "Remboursement caisse {$order->reference}",
                    'source' => $order,
                ]);
            }

            $order->update([
                'refunded_at' => now(),
                'refund_reason' => $data['reason'],
                'status' => $amount >= $order->total ? 'cancelled' : $order->status,
            ]);

            // Remboursement total → on recrédite le stock consommé.
            if ($amount >= $order->total) {
                RecipeConsumption::applyForOrder($order, 'in');
            }
        });

        AuditLog::record('pos.refund', $order, [
            'amount' => $amount, 'reason' => $data['reason'], 'authorized_by' => $manager->id,
        ]);

        return response()->json(['ok' => true, 'refunded' => $amount]);
    }

    /** Pré-vérification PIN (retour immédiat pour l'UI). */
    public function authorize(Request $request)
    {
        $pin = (string) $request->input('pin', '');
        $manager = PosRegister::authorizeManager($pin);

        return response()->json(['ok' => (bool) $manager, 'manager' => $manager?->name]);
    }

    public function receipt(Order $order)
    {
        abort_unless($order->source === 'pos', 404);

        return view('admin.pos.receipt', [
            'order' => $order->load('items', 'venue', 'reservation.guest', 'cashier', 'server', 'payments'),
        ]);
    }

    /* ============================ Session de caisse ============================ */

    public function openSession(Request $request)
    {
        $data = $request->validate([
            'finance_account_id' => ['required', Rule::exists('finance_accounts', 'id')->where('type', 'cash')],
            'opening_float' => ['required', 'integer', 'min:0'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $account = FinanceAccount::findOrFail($data['finance_account_id']);
        $denoms = array_filter($data['denominations'] ?? [], fn ($n) => (int) $n > 0);

        PosRegister::open($account, $request->user(), $data['opening_float'], $denoms);

        return redirect()->route('admin.pos.index')->with('status', 'Caisse ouverte.');
    }

    public function closeSession(Request $request, CashSession $cashSession)
    {
        abort_unless($cashSession->status === 'open', 422);

        $data = $request->validate([
            'counted_amount' => ['required', 'integer', 'min:0'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:250'],
        ]);

        $denoms = array_filter($data['denominations'] ?? [], fn ($n) => (int) $n > 0);
        $session = PosRegister::close($cashSession, $request->user(), $data['counted_amount'], $denoms, $data['note'] ?? null);

        return view('admin.pos.zreport', [
            'session' => $session->load('account', 'openedBy', 'closedBy'),
            'report' => $session->report(),
        ]);
    }

    public function sessionReport()
    {
        $account = $this->cashAccount();
        $session = $account ? PosRegister::openFor($account) : null;
        abort_unless($session, 404, 'Aucune caisse ouverte.');

        return view('admin.pos.zreport', [
            'session' => $session->load('account', 'openedBy'),
            'report' => $session->report(),
            'live' => true,
        ]);
    }

    /* ============================== Plan de salle ============================== */

    public function floor(Request $request)
    {
        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $venue = $request->filled('venue')
            ? $venues->firstWhere('id', $request->integer('venue'))
            : $venues->firstWhere('is_room_service', false) ?? $venues->first();
        abort_unless($venue, 404, 'Aucun point de restauration configuré.');

        $tables = QrLocation::tables()->where('venue_id', $venue->id)
            ->orderByRaw('zone is null')->orderBy('zone')->orderBy('label')
            ->get()
            ->map(function (QrLocation $t) {
                $order = $t->currentOrder()?->loadCount('items')->load('server');

                return [
                    'id' => $t->id,
                    'label' => $t->label,
                    'zone' => $t->zone ?: 'Sans zone',
                    'seats' => $t->seats,
                    'status' => $order ? 'occupee' : 'libre',
                    'order_id' => $order?->id,
                    'covers' => $order?->items_count,
                    'server' => $order?->server?->name,
                    'total' => $order?->total,
                    'since' => $order?->placed_at?->diffForHumans(null, true),
                ];
            })
            ->groupBy('zone');

        return view('admin.pos.floor', [
            'venue' => $venue,
            'venues' => $venues,
            'zones' => $tables,
        ]);
    }

    /* ========================= Recherche client hôtel ========================= */

    public function guests(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $breakfastKey = config('pos.breakfast.extra_key');
        $checkedToday = BreakfastCheckin::whereDate('service_date', today())->pluck('service_date', 'reservation_id');

        $rows = Reservation::query()
            ->where('status', 'checked_in')
            ->with('guest', 'room')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('reference', 'like', "%{$q}%")
                        ->orWhere('group_reference', 'like', "%{$q}%")
                        ->orWhereHas('room', fn ($r) => $r->where('number', 'like', "%{$q}%"))
                        ->orWhereHas('guest', fn ($g) => $g
                            ->where('last_name', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%"));
                });
            })
            ->orderBy('room_id')
            ->limit(15)
            ->get()
            ->map(fn (Reservation $r) => [
                'id' => $r->id,
                'reference' => $r->groupKey(),
                'room' => $r->room->number ?? '—',
                'guest' => $r->guest->fullName(),
                'folio' => GuestFolio::for($r),
                'breakfast' => [
                    'included' => in_array($breakfastKey, (array) $r->extras, true),
                    'checked_today' => $checkedToday->has($r->id),
                ],
            ]);

        return response()->json(['results' => $rows]);
    }

    /* ============================== Room service ============================== */

    public function roomService()
    {
        $rank = ['ready' => 0, 'out_for_delivery' => 1, 'delivered' => 2];

        $orders = Order::query()
            ->where('type', 'room_service')
            ->with('items', 'room', 'reservation.guest')
            ->where(function ($q) {
                $q->whereIn('status', ['ready', 'out_for_delivery'])
                    ->orWhere(fn ($s) => $s->where('status', 'delivered')->where('delivered_at', '>=', now()->subHours(2)));
            })
            ->orderBy('ready_at')
            ->get()
            ->sortBy(fn (Order $o) => $rank[$o->status] ?? 9)
            ->values();

        return view('admin.pos.room-service', ['orders' => $orders]);
    }

    /* ========================== Contrôle petit-déjeuner ========================== */

    public function breakfast()
    {
        return view('admin.pos.breakfast', [
            'today' => today(),
            'recent' => BreakfastCheckin::whereDate('service_date', today())
                ->with('reservation.guest', 'reservation.room')->latest()->take(20)->get(),
        ]);
    }

    public function breakfastStore(Request $request)
    {
        $data = $request->validate([
            'reservation_id' => ['required', Rule::exists('reservations', 'id')->where('status', 'checked_in')],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $reservation = Reservation::with('guest', 'room')->findOrFail($data['reservation_id']);
        $included = in_array(config('pos.breakfast.extra_key'), (array) $reservation->extras, true);

        // Recherche par whereDate : le cast `date` stocke un datetime en SQLite,
        // ce qui fait échouer un firstOrNew sur une date brute.
        $checkin = BreakfastCheckin::where('reservation_id', $reservation->id)
            ->whereDate('service_date', today())
            ->first()
            ?? new BreakfastCheckin([
                'reservation_id' => $reservation->id,
                'service_date' => today()->toDateString(),
            ]);
        $isNew = ! $checkin->exists;

        DB::transaction(function () use ($checkin, $reservation, $data, $included, $request) {
            $checkin->fill([
                'guests' => $data['guests'],
                'included' => $included,
                'recorded_by' => $request->user()->id,
            ])->save();

            if (! $included) {
                $unit = (int) (collect(config('booking.extras'))->firstWhere('key', config('pos.breakfast.extra_key'))['price'] ?? 0);
                $amount = $unit * $data['guests'];
                $label = "Petit-déjeuner ×{$data['guests']}";

                if ($checkin->charge) {
                    $checkin->charge->update(['amount' => $amount, 'label' => $label]);
                } elseif ($amount > 0) {
                    $charge = $reservation->charges()->create([
                        'source_type' => $checkin->getMorphClass(),
                        'source_id' => $checkin->id,
                        'label' => $label,
                        'amount' => $amount,
                        'created_by' => $request->user()->id,
                    ]);
                    $checkin->update(['reservation_charge_id' => $charge->id]);
                }
            } elseif ($checkin->charge) {
                // Le séjour inclut désormais le PDJ : on retire une imputation devenue caduque.
                $checkin->charge->delete();
                $checkin->update(['reservation_charge_id' => null]);
            }
        });

        AuditLog::record('pos.breakfast', $checkin, ['reservation' => $reservation->reference, 'guests' => $data['guests'], 'included' => $included]);

        $msg = $isNew ? 'Petit-déjeuner enregistré' : 'Passage mis à jour';
        $msg .= $included ? ' — inclus dans le séjour.' : " — imputé au folio (chambre {$reservation->room?->number}).";

        return back()->with('status', $msg);
    }

    /* ============================= Facture A4 ============================= */

    public function invoice(Order $order)
    {
        abort_unless($order->source === 'pos', 404);
        $this->ensureInvoiceNumber($order);

        return view('admin.pos.invoice', [
            'order' => $order->load('items', 'venue', 'reservation.guest', 'room', 'cashier', 'server', 'payments'),
            'hotel' => Hotel::current(),
        ]);
    }

    private function ensureInvoiceNumber(Order $order): void
    {
        if ($order->invoice_number) {
            return;
        }

        DB::transaction(function () use ($order) {
            $year = now()->year;
            $seq = Sequence::next("pos_invoice:{$year}");
            $order->update([
                'invoice_number' => sprintf('KH-%d-%06d', $year, $seq),
                'invoiced_at' => now(),
            ]);
        });
    }

    public function sendInvoice(Request $request, Order $order)
    {
        abort_unless($order->source === 'pos', 404);

        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->ensureInvoiceNumber($order);

        $email = ($data['email'] ?? null) ?: $order->reservation?->guest?->email;
        $phone = ($data['phone'] ?? null) ?: $order->reservation?->guest?->phone;

        abort_if(! $email && ! $phone, 422, 'Aucune adresse e-mail ni téléphone pour envoyer la facture.');

        if ($email) {
            Mail::to($email)->queue(new PosInvoice($order));
        }
        if ($phone) {
            Sms::queueTemplate($phone, 'pos_invoice', [
                'ref' => $order->invoice_number,
                'url' => route('admin.pos.invoice', $order),
            ]);
        }

        AuditLog::record('pos.invoice.sent', $order, ['email' => $email, 'phone' => $phone]);

        return back()->with('status', 'Facture '.$order->invoice_number.' envoyée.');
    }

    /* =============================== PIN caissier =============================== */

    public function editPin()
    {
        return view('admin.pos.pin');
    }

    public function updatePin(Request $request)
    {
        [$min, $max] = [config('pos.pin.min', 4), config('pos.pin.max', 6)];

        $data = $request->validate([
            'pos_pin' => ['required', 'digits_between:'.$min.','.$max, 'confirmed'],
        ], [], ['pos_pin' => 'PIN']);

        $request->user()->update(['pos_pin' => $data['pos_pin']]);
        AuditLog::record('pos.pin.set', $request->user());

        return redirect()->route('admin.pos.index')->with('status', 'PIN caisse enregistré.');
    }

    /* ------------------------------------------------------------------ */

    private function cashAccount(): ?FinanceAccount
    {
        if (! Schema::hasTable('finance_accounts')) {
            return null;
        }

        return FinanceAccount::where('hotel_id', Hotel::current()->id)
            ->where('type', 'cash')->where('is_active', true)
            ->when(config('pos.cash_account_code'), fn ($q, $code) => $q->orderByRaw('code = ? desc', [$code]))
            ->orderBy('id')
            ->first();
    }

    private function discountNeedsAuthorization(array $quote): bool
    {
        if (($quote['discount'] ?? 0) <= 0) {
            return false;
        }

        return ($quote['discount_percent'] ?? 0) > config('pos.discount.max_percent', 100)
            || $quote['discount'] > config('pos.discount.max_amount', PHP_INT_MAX);
    }

    private function requireManager(?string $pin, string $what): User
    {
        $manager = PosRegister::authorizeManager((string) $pin);

        abort_if(! $manager, 422, "PIN manager requis ou invalide pour {$what}.");

        return $manager;
    }

    /**
     * Écrit les lignes de règlement + les mouvements financiers correspondants.
     *
     * @param  array<int,array{method:string,amount:int,tendered?:int,reservation_id?:int}>  $payments
     */
    private function settlePayments(Order $order, array $payments, ?CashSession $session, ?User $manager, User $cashier): void
    {
        $account = $this->cashAccount();
        $hasRoom = false;
        $hasMoney = false;

        foreach ($payments as $p) {
            $method = $p['method'];
            $amount = (int) $p['amount'];

            if ($method === 'chambre') {
                $reservation = Reservation::findOrFail($p['reservation_id']);
                $reservation->charges()->create([
                    'source_type' => $order->getMorphClass(),
                    'source_id' => $order->id,
                    'label' => "Caisse {$order->reference} — ".($order->venue->name ?? 'Restaurant'),
                    'amount' => $amount,
                    'created_by' => $cashier->id,
                ]);
                $order->forceFill(['reservation_id' => $reservation->id, 'room_id' => $reservation->room_id]);

                $order->payments()->create([
                    'method' => 'chambre',
                    'amount' => $amount,
                    'reservation_id' => $reservation->id,
                    'recorded_by' => $cashier->id,
                    'authorized_by' => $manager?->id,
                ]);

                $hasRoom = true;

                continue;
            }

            $hasMoney = true;

            if ($amount > 0 && Schema::hasTable('finance_accounts') && FinanceAccount::query()->exists()) {
                FinanceLedger::record([
                    'direction' => 'income',
                    'category' => 'restaurant',
                    'method' => self::METHOD_MAP[$method] ?? 'especes',
                    'amount' => $amount,
                    'label' => "Caisse {$order->reference} — ".self::SALE_TYPES[$order->sale_type],
                    'finance_account_id' => $method === 'especes' ? $account?->id : null,
                    'source' => $order,
                ]);
            }

            $order->payments()->create([
                'method' => $method,
                'amount' => $amount,
                'tendered' => $p['tendered'] ?? null,
                'cash_session_id' => $method === 'especes' ? $session?->id : null,
                'recorded_by' => $cashier->id,
                'authorized_by' => $manager?->id,
            ]);
        }

        $order->forceFill([
            'payment_status' => (! $hasMoney && $hasRoom) ? 'charged_to_room' : 'paid',
            'amount_tendered' => data_get(collect($payments)->firstWhere('method', 'especes'), 'tendered'),
        ])->save();
    }

    private function menuTree(Venue $venue)
    {
        $categoryIds = $venue->menuCategories()->pluck('menu_categories.id');

        return MenuCategory::whereIn('id', $categoryIds)->active()->ordered()
            ->with(['items' => fn ($q) => $q->available()->with('optionGroups.options')])
            ->get()
            ->map(fn (MenuCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $this->categoryIcon($c->name),
                'items' => $c->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'price' => $i->price,
                    'image' => $this->thumb($i->imageUrl()),
                    'has_options' => $i->optionGroups->isNotEmpty(),
                    'option_groups' => $i->optionGroups->map(fn ($g) => [
                        'id' => $g->id, 'name' => $g->name, 'type' => $g->type, 'required' => (bool) $g->required,
                        'options' => $g->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->name, 'price_delta' => (int) $o->price_delta]),
                    ]),
                ]),
            ])->values();
    }

    private function reference(): string
    {
        do {
            $ref = 'C'.now()->format('ymd').strtoupper(Str::random(4));
        } while (Order::where('reference', $ref)->exists());

        return $ref;
    }

    /** Vignette légère pour la grille de caisse (Unsplash : on réduit la largeur). */
    private function thumb(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_contains($url, 'w=') ? preg_replace('/([?&])w=\d+/', '$1w=320', $url) : $url;
    }

    /** Émoji indicatif selon le nom de la catégorie (purement décoratif). */
    private function categoryIcon(string $name): string
    {
        $n = mb_strtolower($name);

        return match (true) {
            str_contains($n, 'entrée') || str_contains($n, 'salade') => '🥗',
            str_contains($n, 'plat') || str_contains($n, 'grill') || str_contains($n, 'viande') => '🍽️',
            str_contains($n, 'poisson') || str_contains($n, 'mer') => '🐟',
            str_contains($n, 'burger') || str_contains($n, 'snack') || str_contains($n, 'fast') => '🍔',
            str_contains($n, 'pizza') || str_contains($n, 'pâte') || str_contains($n, 'pasta') => '🍕',
            str_contains($n, 'dessert') || str_contains($n, 'pâtiss') || str_contains($n, 'sucre') => '🍰',
            str_contains($n, 'café') || str_contains($n, 'thé') || str_contains($n, 'chaud') => '☕',
            str_contains($n, 'cocktail') || str_contains($n, 'bar') || str_contains($n, 'spirit') => '🍸',
            str_contains($n, 'vin') || str_contains($n, 'champ') => '🍷',
            str_contains($n, 'bière') => '🍺',
            str_contains($n, 'jus') || str_contains($n, 'soft') || str_contains($n, 'boisson') || str_contains($n, 'eau') => '🥤',
            str_contains($n, 'petit') || str_contains($n, 'déjeuner') || str_contains($n, 'brunch') => '🥐',
            str_contains($n, 'menu') || str_contains($n, 'formule') => '📋',
            str_contains($n, 'enfant') => '🧒',
            default => '🍴',
        };
    }
}
