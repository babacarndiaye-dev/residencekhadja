<?php

namespace App\Services;

use App\Models\DailyMetric;
use App\Models\Event;
use App\Models\EventLead;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\LoyaltyAccount;
use App\Models\MaintenanceTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StockItem;
use App\Models\SupplierInvoice;
use Illuminate\Support\Carbon;

/**
 * Consolidation décisionnelle (§26–28). Toutes les plages [from, to] sont
 * inclusives, en dates. Les méthodes renvoient des tableaux prêts pour la vue.
 */
class Analytics
{
    private static function hotelId(): int
    {
        return Hotel::current()->id;
    }

    private static function sellableRooms(): int
    {
        return Room::where('is_active', true)->whereNotIn('status', ['hors_service'])->count();
    }

    /** Bornes inclusives sûres pour une colonne DATE (SQLite stocke parfois l'heure). */
    private static function dateBounds(Carbon $from, Carbon $to): array
    {
        return [$from->toDateString(), $to->toDateString().' 23:59:59'];
    }

    /* ----------------------------- Hébergement ---------------------------- */

    public static function occupancy(Carbon $from, Carbon $to): array
    {
        $sellable = self::sellableRooms();
        $days = 0;
        $roomsSold = 0;
        $roomRevenue = 0.0;

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days++;
            $stays = Reservation::stayingOn($d)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->get(['rooms_count', 'room_total', 'check_in', 'check_out']);

            $roomsSold += (int) $stays->sum('rooms_count');
            $roomRevenue += $stays->sum(fn ($r) => $r->room_total / max(1, nights_between($r->check_in, $r->check_out)));
        }

        $available = max(1, $sellable * $days);

        return [
            'days' => $days,
            'sellable_rooms' => $sellable,
            'rooms_sold' => $roomsSold,
            'room_nights_available' => $sellable * $days,
            'occupancy' => round($roomsSold / $available * 100, 1),
            'adr' => $roomsSold > 0 ? (int) round($roomRevenue / $roomsSold) : 0,
            'revpar' => (int) round($roomRevenue / $available),
            'room_revenue' => (int) round($roomRevenue),
        ];
    }

    public static function pms(Carbon $from, Carbon $to): array
    {
        $created = Reservation::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $total = (clone $created)->count();
        $cancelled = (clone $created)->whereIn('status', ['cancelled', 'no_show'])->count();

        $leadDays = (clone $created)->get(['created_at', 'check_in'])
            ->map(fn ($r) => $r->created_at->startOfDay()->diffInDays(Carbon::parse($r->check_in), false))
            ->filter(fn ($v) => $v >= 0);

        $los = Reservation::whereBetween('check_in', self::dateBounds($from, $to))
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get(['check_in', 'check_out'])
            ->map(fn ($r) => nights_between($r->check_in, $r->check_out));

        return [
            'bookings_created' => $total,
            'bookings_cancelled' => $cancelled,
            'cancellation_rate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
            'avg_lead_time' => $leadDays->isNotEmpty() ? round($leadDays->avg(), 1) : 0,
            'avg_length_of_stay' => $los->isNotEmpty() ? round($los->avg(), 1) : 0,
            'channel_mix' => (clone $created)->selectRaw('channel, count(*) as t')->groupBy('channel')->pluck('t', 'channel')->all(),
        ];
    }

    /* -------------------------------- Finance --------------------------- */

    public static function revenue(Carbon $from, Carbon $to): array
    {
        $rows = FinanceTransaction::whereBetween('operation_date', self::dateBounds($from, $to))
            ->selectRaw('direction, category, SUM(amount) as total')
            ->groupBy('direction', 'category')->get();

        $income = $rows->where('direction', 'income');
        $byCat = $income->pluck('total', 'category')->map(fn ($v) => (int) $v)->all();

        $room = (int) ($byCat['hebergement'] ?? 0);
        $fnb = (int) ($byCat['restaurant'] ?? 0);
        $totalIncome = (int) $income->sum('total');

        return [
            'total_income' => $totalIncome,
            'total_expense' => (int) $rows->where('direction', 'expense')->sum('total'),
            'room_revenue' => $room,
            'fnb_revenue' => $fnb,
            'other_revenue' => $totalIncome - $room - $fnb,
            'by_category' => $byCat,
            'result' => $totalIncome - (int) $rows->where('direction', 'expense')->sum('total'),
        ];
    }

    public static function apAr(): array
    {
        $receivables = Reservation::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->get()
            ->sum(fn (Reservation $r) => max(0, $r->balance()));
        $payables = (int) SupplierInvoice::where('status', '!=', 'paid')->sum('total');

        return ['receivables' => (int) $receivables, 'payables' => $payables];
    }

    /* ------------------------------ Restauration ------------------------ */

    public static function fnb(Carbon $from, Carbon $to): array
    {
        $orders = Order::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled');

        $count = (clone $orders)->count();
        $revenue = (int) (clone $orders)->sum('total');
        $roomService = (clone $orders)->whereNotNull('reservation_id')->count();

        $topItems = OrderItem::whereHas('order', fn ($q) => $q
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled'))
            ->selectRaw('name, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('name')->orderByDesc('revenue')->limit(10)->get();

        return [
            'orders' => $count,
            'revenue' => $revenue,
            'avg_check' => $count > 0 ? (int) round($revenue / $count) : 0,
            'room_service_share' => $count > 0 ? round($roomService / $count * 100, 1) : 0,
            'top_items' => $topItems,
        ];
    }

    /* ----------------------- Reporting POS (P3) ----------------------- */

    /** Chiffre d'affaires POS : brut, remises, remboursements, ventilation par type et par moyen. */
    public static function posSales(Carbon $from, Carbon $to): array
    {
        $bounds = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $orders = Order::where('source', 'pos')->whereBetween('created_at', $bounds);
        $active = (clone $orders)->where('status', '!=', 'cancelled');

        $gross = (int) (clone $active)->sum('total');
        $discounts = (int) (clone $active)->sum('discount');
        $refunds = (int) OrderPayment::whereHas('order', fn ($q) => $q
            ->where('source', 'pos')->whereBetween('created_at', $bounds))
            ->where('amount', '<', 0)->sum('amount');

        $byType = (clone $active)->selectRaw('sale_type, count(*) as n, sum(total) as t')
            ->groupBy('sale_type')->get()->mapWithKeys(fn ($r) => [$r->sale_type => ['orders' => (int) $r->n, 'total' => (int) $r->t]]);

        $byMethod = OrderPayment::whereHas('order', fn ($q) => $q
            ->where('source', 'pos')->whereBetween('created_at', $bounds)->where('status', '!=', 'cancelled'))
            ->selectRaw('method, sum(amount) as t')->groupBy('method')->pluck('t', 'method')->map(fn ($v) => (int) $v);

        $count = (int) (clone $active)->count();

        return [
            'orders' => $count,
            'gross' => $gross,
            'discounts' => $discounts,
            'refunds' => abs($refunds),
            'net' => $gross - abs($refunds),
            'avg_check' => $count > 0 ? (int) round($gross / $count) : 0,
            'by_type' => $byType,
            'by_method' => $byMethod,
        ];
    }

    /** Meilleures ventes POS avec coût matière et marge (recettes). */
    public static function posBestsellers(Carbon $from, Carbon $to): array
    {
        $bounds = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $rows = OrderItem::whereHas('order', fn ($q) => $q
            ->where('source', 'pos')->whereBetween('created_at', $bounds)->where('status', '!=', 'cancelled'))
            ->selectRaw('menu_item_id, name, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('menu_item_id', 'name')->orderByDesc('revenue')->limit(25)->get();

        $items = MenuItem::with('recipe.stockItem')
            ->whereIn('id', $rows->pluck('menu_item_id')->filter())->get()->keyBy('id');

        return $rows->map(function ($r) use ($items) {
            $unitCost = $r->menu_item_id ? (int) ($items[$r->menu_item_id]?->foodCost() ?? 0) : 0;
            $cost = $unitCost * (int) $r->qty;
            $revenue = (int) $r->revenue;

            return [
                'name' => $r->name,
                'qty' => (int) $r->qty,
                'revenue' => $revenue,
                'cost' => $cost,
                'margin' => $revenue - $cost,
                'margin_pct' => $revenue > 0 ? round(($revenue - $cost) / $revenue * 100, 1) : null,
            ];
        })->all();
    }

    /** Performance par serveur (POS). */
    public static function posServers(Carbon $from, Carbon $to): array
    {
        $bounds = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        return Order::where('source', 'pos')->whereBetween('created_at', $bounds)->whereNotNull('server_id')
            ->with('server:id,name')
            ->get()
            ->groupBy('server_id')
            ->map(fn ($group) => [
                'server' => $group->first()->server?->name ?? '—',
                'tickets' => $group->where('status', '!=', 'cancelled')->count(),
                'revenue' => (int) $group->where('status', '!=', 'cancelled')->sum('total'),
                'discounts' => (int) $group->sum('discount'),
                'cancelled' => $group->where('status', 'cancelled')->count(),
                'refunded' => $group->whereNotNull('refunded_at')->count(),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /* ---------------------------- Opérations --------------------------- */

    public static function operations(Carbon $from, Carbon $to): array
    {
        $tasks = HousekeepingTask::whereBetween('service_date', self::dateBounds($from, $to));
        $tasksTotal = (clone $tasks)->count();
        $tasksDone = (clone $tasks)->whereIn('status', ['done', 'inspected'])->count();
        $inspected = (clone $tasks)->where('status', 'inspected')->count();
        $passed = (clone $tasks)->where('status', 'inspected')->where('qc_score', '>=', config('housekeeping.qc_pass_score', 80))->count();

        return [
            'hk_tasks' => $tasksTotal,
            'hk_tasks_done' => $tasksDone,
            'hk_completion_rate' => $tasksTotal > 0 ? round($tasksDone / $tasksTotal * 100, 1) : 0,
            'qc_pass_rate' => $inspected > 0 ? round($passed / $inspected * 100, 1) : 0,
            'maintenance_open' => MaintenanceTicket::open()->count(),
            'maintenance_critical' => MaintenanceTicket::open()->where('priority', 'critical')->count(),
        ];
    }

    public static function stock(): array
    {
        $items = StockItem::query()->where('is_active', true)->get();

        return [
            'stock_value' => (int) $items->sum(fn (StockItem $i) => $i->stockValue()),
            'items_below_threshold' => $items->filter(fn (StockItem $i) => $i->isBelowThreshold())->count(),
            'sku_count' => $items->count(),
        ];
    }

    /* -------------------------------- CRM ------------------------------ */

    public static function crm(Carbon $from, Carbon $to): array
    {
        $guests = Guest::count();
        $optedIn = Guest::where('marketing_opt_in', true)->count();
        $withStay = Guest::whereHas('reservations', fn ($q) => $q->whereIn('status', ['checked_in', 'checked_out']))->count();
        $repeat = Guest::whereHas('reservations', fn ($q) => $q->whereIn('status', ['checked_in', 'checked_out']), '>=', 2)->count();

        return [
            'new_members' => LoyaltyAccount::whereBetween('enrolled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count(),
            'members_total' => LoyaltyAccount::count(),
            'points_liability' => (int) (LoyaltyAccount::sum('points_balance') * (int) config('loyalty.point_value_fcfa', 5)),
            'optin_rate' => $guests > 0 ? round($optedIn / $guests * 100, 1) : 0,
            'repeat_guest_rate' => $withStay > 0 ? round($repeat / $withStay * 100, 1) : 0,
        ];
    }

    /* ----------------------------- Événements -------------------------- */

    public static function events(Carbon $from, Carbon $to): array
    {
        $leads = EventLead::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $created = (clone $leads)->count();
        $won = (clone $leads)->where('status', 'gagne')->count();
        $lost = (clone $leads)->where('status', 'perdu')->count();

        $confirmedRevenue = Event::whereIn('status', ['confirme', 'realise'])
            ->whereBetween('starts_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with('quote')->get()->sum(fn (Event $e) => $e->contractValue());

        return [
            'leads_created' => $created,
            'win_rate' => ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : 0,
            'pipeline_open_value' => (int) EventLead::whereIn('status', EventLead::OPEN)->sum('estimated_value'),
            'confirmed_revenue' => (int) $confirmedRevenue,
        ];
    }

    /* ------------------------------ Paiements -------------------------- */

    public static function payments(Carbon $from, Carbon $to): array
    {
        $intents = PaymentIntent::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $total = (clone $intents)->count();
        $paid = (clone $intents)->where('status', 'paid')->count();
        $paidAmount = (int) (clone $intents)->where('status', 'paid')->sum('amount');
        $refunded = (int) PaymentIntent::whereBetween('refunded_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', 'refunded')->sum('amount');

        $income = self::revenue($from, $to)['total_income'];

        return [
            'intents' => $total,
            'paid' => $paid,
            'success_rate' => $total > 0 ? round($paid / $total * 100, 1) : 0,
            'online_amount' => $paidAmount,
            'refunded' => $refunded,
            'online_share' => $income > 0 ? round($paidAmount / $income * 100, 1) : 0,
        ];
    }

    /* --------------------------- Vue d'ensemble ------------------------ */

    public static function overview(Carbon $from, Carbon $to): array
    {
        $occ = self::occupancy($from, $to);
        $rev = self::revenue($from, $to);
        $fnb = self::fnb($from, $to);
        $pms = self::pms($from, $to);
        $pay = self::payments($from, $to);
        $crm = self::crm($from, $to);
        $evt = self::events($from, $to);
        $t = config('bi.targets');

        return [
            'kpi' => [
                ['key' => 'occupancy', 'label' => 'Taux d’occupation', 'value' => $occ['occupancy'], 'unit' => '%', 'target' => $t['occupancy'], 'good' => $occ['occupancy'] >= $t['occupancy']],
                ['key' => 'adr', 'label' => 'Prix moyen (ADR)', 'value' => $occ['adr'], 'unit' => 'FCFA', 'target' => $t['adr'], 'good' => $occ['adr'] >= $t['adr']],
                ['key' => 'revpar', 'label' => 'RevPAR', 'value' => $occ['revpar'], 'unit' => 'FCFA', 'target' => $t['revpar'], 'good' => $occ['revpar'] >= $t['revpar']],
                ['key' => 'fnb_avg_check', 'label' => 'Ticket moyen F&B', 'value' => $fnb['avg_check'], 'unit' => 'FCFA', 'target' => $t['fnb_avg_check'], 'good' => $fnb['avg_check'] >= $t['fnb_avg_check']],
                ['key' => 'cancellation_rate', 'label' => 'Taux d’annulation', 'value' => $pms['cancellation_rate'], 'unit' => '%', 'target' => $t['cancellation_rate'], 'good' => $pms['cancellation_rate'] <= $t['cancellation_rate']],
                ['key' => 'online_payment_share', 'label' => 'Part paiement en ligne', 'value' => $pay['online_share'], 'unit' => '%', 'target' => $t['online_payment_share'], 'good' => $pay['online_share'] >= $t['online_payment_share']],
                ['key' => 'loyalty_optin_rate', 'label' => 'Opt-in marketing', 'value' => $crm['optin_rate'], 'unit' => '%', 'target' => $t['loyalty_optin_rate'], 'good' => $crm['optin_rate'] >= $t['loyalty_optin_rate']],
                ['key' => 'event_win_rate', 'label' => 'Taux de conversion MICE', 'value' => $evt['win_rate'], 'unit' => '%', 'target' => $t['event_win_rate'], 'good' => $evt['win_rate'] >= $t['event_win_rate']],
            ],
            'occupancy' => $occ,
            'revenue' => $rev,
            'fnb' => $fnb,
            'pms' => $pms,
            'payments' => $pay,
            'crm' => $crm,
            'events' => $evt,
            'operations' => self::operations($from, $to),
            'stock' => self::stock(),
            'ap_ar' => self::apAr(),
            'treasury' => (int) FinanceAccount::where('is_active', true)->get()->sum(fn ($a) => $a->balance()),
        ];
    }

    /* --------------------------- Instantané ---------------------------- */

    /** Calcule et stocke les métriques d'une journée. Renvoie le nombre de clés écrites. */
    public static function snapshot(Carbon $date): int
    {
        $date = $date->copy()->startOfDay();
        $occ = self::occupancy($date, $date);
        $rev = self::revenue($date, $date);
        $fnb = self::fnb($date, $date);
        $pms = self::pms($date, $date);
        $ops = self::operations($date, $date);
        $pay = self::payments($date, $date);

        $values = [
            'rooms_sold' => $occ['rooms_sold'],
            'occupancy' => $occ['occupancy'],
            'adr' => $occ['adr'],
            'revpar' => $occ['revpar'],
            'room_revenue' => $rev['room_revenue'],
            'fnb_revenue' => $rev['fnb_revenue'],
            'other_revenue' => $rev['other_revenue'],
            'total_revenue' => $rev['total_income'],
            'total_expense' => $rev['total_expense'],
            'bookings_created' => $pms['bookings_created'],
            'bookings_cancelled' => $pms['bookings_cancelled'],
            'covers' => $fnb['orders'],
            'hk_tasks' => $ops['hk_tasks'],
            'hk_tasks_done' => $ops['hk_tasks_done'],
            'maintenance_open' => $ops['maintenance_open'],
            'treasury' => (int) FinanceAccount::where('is_active', true)->get()->sum(fn ($a) => $a->balance()),
            'stock_value' => self::stock()['stock_value'],
            'online_payments' => $pay['paid'],
            'online_payment_amount' => $pay['online_amount'],
        ];

        $hotelId = self::hotelId();
        foreach ($values as $key => $value) {
            // Recherche par whereDate : le cast `date` ferait rater la ligne existante sur SQLite.
            $metric = DailyMetric::where('hotel_id', $hotelId)
                ->where('key', $key)
                ->whereDate('metric_date', $date->toDateString())
                ->first()
                ?? new DailyMetric(['hotel_id' => $hotelId, 'metric_date' => $date->toDateString(), 'key' => $key]);

            $metric->value = $value;
            $metric->save();
        }

        return count($values);
    }

    /** Série [Y-m-d => float] pour un KPI, trous comblés à null. */
    public static function series(string $key, Carbon $from, Carbon $to): array
    {
        $rows = DailyMetric::where('hotel_id', self::hotelId())
            ->where('key', $key)
            ->whereBetween('metric_date', self::dateBounds($from, $to))
            ->get(['metric_date', 'value'])
            ->keyBy(fn ($m) => $m->metric_date->toDateString())
            ->map(fn ($m) => (float) $m->value);

        $out = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $iso = $d->toDateString();
            $out[$iso] = $rows[$iso] ?? null;
        }

        return $out;
    }

    /** Mini-graphe SVG (polyline) sans librairie. */
    public static function sparkline(array $series, int $w = 160, int $h = 36): string
    {
        $vals = array_values(array_map(fn ($v) => $v === null ? 0 : (float) $v, $series));
        $n = count($vals);
        if ($n < 2) {
            return '<svg width="'.$w.'" height="'.$h.'"></svg>';
        }
        $min = min($vals);
        $max = max($vals);
        $span = ($max - $min) ?: 1;

        $pts = [];
        foreach ($vals as $i => $v) {
            $x = round($i / ($n - 1) * ($w - 2) + 1, 1);
            $y = round($h - 1 - ($v - $min) / $span * ($h - 2), 1);
            $pts[] = "{$x},{$y}";
        }
        $last = end($vals);
        $up = $last >= reset($vals);
        $color = $up ? '#2f855a' : '#a73514';

        return '<svg width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none">'
            .'<polyline fill="none" stroke="'.$color.'" stroke-width="1.5" points="'.implode(' ', $pts).'"/></svg>';
    }

    /* ------------------------------------------------------------------ */

    public static function resolveRange(?string $period, ?string $from, ?string $to): array
    {
        if ($from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->startOfDay()];
        }

        return match ($period) {
            'today' => [Carbon::today(), Carbon::today()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
            'mtd' => [Carbon::today()->startOfMonth(), Carbon::today()],
            '90d' => [Carbon::today()->subDays(89), Carbon::today()],
            '7d' => [Carbon::today()->subDays(6), Carbon::today()],
            default => [Carbon::today()->subDays(29), Carbon::today()],
        };
    }
}
