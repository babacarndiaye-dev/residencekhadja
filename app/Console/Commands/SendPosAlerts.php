<?php

namespace App\Console\Commands;

use App\Models\CashSession;
use App\Models\Order;
use App\Models\StockItem;
use App\Support\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendPosAlerts extends Command
{
    protected $signature = 'pos:alerts';

    protected $description = 'Alertes caisse restaurant : stock bas, cuisine en retard, caisse ouverte trop longtemps, annulations anormales';

    public function handle(): int
    {
        $sent = 0;
        $sent += $this->lowRecipeStock();
        $sent += $this->lateKitchen();
        $sent += $this->staleCashSessions();
        $sent += $this->cancellationRate();

        $this->info("{$sent} alerte(s) émise(s).");

        return self::SUCCESS;
    }

    /** Évite de répéter la même alerte le même jour. */
    private function once(string $key, callable $fn): int
    {
        $cacheKey = 'pos.alert.'.$key.'.'.today()->toDateString();
        if (Cache::has($cacheKey)) {
            return 0;
        }

        Cache::put($cacheKey, 1, now()->endOfDay());
        $fn();

        return 1;
    }

    private function lowRecipeStock(): int
    {
        $items = StockItem::active()
            ->whereHas('menuItemRecipes')
            ->get()
            ->filter->isBelowThreshold();

        $count = 0;
        foreach ($items as $item) {
            $count += $this->once("low_stock.{$item->id}", fn () => Notify::roles(
                ['restaurant', 'stock', 'direction'],
                'Stock bas — '.$item->name,
                'Sur le seuil de réapprovisionnement (min '.$item->min_qty.'). Utilisé dans une recette de la carte.',
                route('admin.stock.index', absolute: false),
                level: 'warning',
                icon: '📦',
            ));
        }

        return $count;
    }

    private function lateKitchen(): int
    {
        $limit = now()->subMinutes((int) config('pos.alerts.kitchen_late_minutes', 20));
        $late = Order::whereIn('status', ['new', 'preparing'])
            ->whereNotNull('placed_at')->where('placed_at', '<', $limit)
            ->whereHas('items', fn ($q) => $q->where('needs_kitchen', true))
            ->count();

        if ($late === 0) {
            return 0;
        }

        return $this->once('late_kitchen.'.now()->format('H'), fn () => Notify::roles(
            ['restaurant', 'direction'],
            $late.' commande(s) en retard en cuisine',
            'Au moins '.config('pos.alerts.kitchen_late_minutes', 20).' min sans passer à « prête ».',
            route('admin.kds.index', absolute: false),
            level: 'warning',
            icon: '🍳',
        ));
    }

    private function staleCashSessions(): int
    {
        $limit = now()->subHours((int) config('pos.alerts.session_max_hours', 14));
        $stale = CashSession::where('status', 'open')->where('opened_at', '<', $limit)->with('account')->get();

        $count = 0;
        foreach ($stale as $session) {
            $count += $this->once("stale_session.{$session->id}", fn () => Notify::roles(
                ['finance', 'direction'],
                'Caisse ouverte depuis trop longtemps',
                $session->account->name.' — ouverte le '.$session->opened_at->format('d/m à H:i').'. À clôturer.',
                route('admin.finance.cash', absolute: false),
                level: 'warning',
                icon: '💰',
            ));
        }

        return $count;
    }

    private function cancellationRate(): int
    {
        $today = [today()->startOfDay(), today()->endOfDay()];
        $orders = Order::where('source', 'pos')->whereBetween('created_at', $today);
        $total = (clone $orders)->count();

        if ($total < 10) {
            return 0;
        }

        $bad = (clone $orders)->where('status', 'cancelled')->count()
            + (clone $orders)->whereNotNull('refunded_at')->count();
        $rate = $bad / $total;

        if ($rate <= (float) config('pos.alerts.cancel_rate_threshold', 0.15)) {
            return 0;
        }

        return $this->once('cancel_rate', fn () => Notify::roles(
            ['direction'],
            'Taux d’annulation/remboursement élevé',
            round($rate * 100, 1)."% des tickets POS du jour ({$bad}/{$total}).",
            route('admin.pos.reports', absolute: false),
            level: 'critical',
            icon: '⚠️',
        ));
    }
}
