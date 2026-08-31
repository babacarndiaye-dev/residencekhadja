<?php

namespace App\Services;

use App\Models\EventLead;
use App\Models\LoyaltyAccount;
use App\Models\OrderItem;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Models\StockItem;
use App\Models\SupplierInvoice;
use Illuminate\Support\Carbon;

/**
 * Bibliothèque de rapports exportables. Chaque rapport renvoie
 * ['key','label','columns','rows'] où rows est une liste de tableaux associatifs.
 */
class ReportRegistry
{
    public static function definitions(): array
    {
        return config('bi.reports');
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, config('bi.reports'));
    }

    public static function run(string $key, Carbon $from, Carbon $to): array
    {
        abort_unless(self::exists($key), 404);

        [$columns, $rows] = match ($key) {
            'reservations' => self::reservations($from, $to),
            'occupancy_daily' => self::occupancyDaily($from, $to),
            'arrivals' => self::movements($from, $to, 'check_in'),
            'departures' => self::movements($from, $to, 'check_out'),
            'revenue_daily' => self::revenueDaily($from, $to),
            'payments' => self::payments($from, $to),
            'ap_ar' => self::apAr(),
            'fnb_items' => self::fnbItems($from, $to),
            'pos_bestsellers' => self::posBestsellers($from, $to),
            'pos_servers' => self::posServers($from, $to),
            'stock_valuation' => self::stockValuation(),
            'loyalty_members' => self::loyaltyMembers($from, $to),
            'events_pipeline' => self::eventsPipeline(),
            default => [[], []],
        };

        return [
            'key' => $key,
            'label' => config("bi.reports.{$key}.label", $key),
            'group' => config("bi.reports.{$key}.group"),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    public static function toCsv(array $report): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $report['columns']);
        foreach ($report['rows'] as $row) {
            fputcsv($out, array_map(fn ($c) => $row[$c] ?? '', $report['columns']));
        }
        rewind($out);

        return stream_get_contents($out);
    }

    /* ----------------------------- Rapports --------------------------- */

    private static function reservations(Carbon $from, Carbon $to): array
    {
        $rows = Reservation::with('guest', 'roomCategory')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Reservation $r) => [
                'Référence' => $r->reference,
                'Client' => $r->guest->fullName(),
                'Arrivée' => $r->check_in->format('Y-m-d'),
                'Départ' => $r->check_out->format('Y-m-d'),
                'Nuits' => $r->nights(),
                'Catégorie' => $r->roomCategory->name,
                'Canal' => $r->channel,
                'Statut' => $r->statusLabel(),
                'Total' => $r->total,
                'Payé' => $r->paidAmount(),
                'Solde' => $r->balance(),
            ])->all();

        return [['Référence', 'Client', 'Arrivée', 'Départ', 'Nuits', 'Catégorie', 'Canal', 'Statut', 'Total', 'Payé', 'Solde'], $rows];
    }

    private static function occupancyDaily(Carbon $from, Carbon $to): array
    {
        $rows = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $o = Analytics::occupancy($d, $d);
            $rows[] = [
                'Date' => $d->format('Y-m-d'),
                'Chambres vendues' => $o['rooms_sold'],
                'Occupation %' => $o['occupancy'],
                'ADR' => $o['adr'],
                'RevPAR' => $o['revpar'],
                'CA hébergement' => $o['room_revenue'],
            ];
        }

        return [['Date', 'Chambres vendues', 'Occupation %', 'ADR', 'RevPAR', 'CA hébergement'], $rows];
    }

    private static function movements(Carbon $from, Carbon $to, string $field): array
    {
        $rows = Reservation::with('guest', 'roomCategory', 'room')
            ->whereBetween($field, [$from->toDateString(), $to->toDateString().' 23:59:59'])
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->orderBy($field)
            ->get()
            ->map(fn (Reservation $r) => [
                'Date' => Carbon::parse($r->$field)->format('Y-m-d'),
                'Référence' => $r->reference,
                'Client' => $r->guest->fullName(),
                'Catégorie' => $r->roomCategory->name,
                'Chambre' => $r->room->number ?? '—',
                'Pax' => $r->adults + $r->children,
                'Statut' => $r->statusLabel(),
            ])->all();

        return [['Date', 'Référence', 'Client', 'Catégorie', 'Chambre', 'Pax', 'Statut'], $rows];
    }

    private static function revenueDaily(Carbon $from, Carbon $to): array
    {
        $rows = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $r = Analytics::revenue($d, $d);
            $rows[] = [
                'Date' => $d->format('Y-m-d'),
                'Hébergement' => $r['room_revenue'],
                'Restauration' => $r['fnb_revenue'],
                'Autres' => $r['other_revenue'],
                'Total recettes' => $r['total_income'],
                'Total dépenses' => $r['total_expense'],
                'Résultat' => $r['result'],
            ];
        }

        return [['Date', 'Hébergement', 'Restauration', 'Autres', 'Total recettes', 'Total dépenses', 'Résultat'], $rows];
    }

    private static function payments(Carbon $from, Carbon $to): array
    {
        $rows = PaymentIntent::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('created_at')
            ->get()
            ->map(fn (PaymentIntent $i) => [
                'Référence' => $i->reference,
                'Objet' => $i->purposeLabel(),
                'Montant' => $i->amount,
                'Moyen' => $i->method ? config("payments.methods.{$i->method}", $i->method) : '',
                'Statut' => $i->statusLabel(),
                'Créé' => $i->created_at->format('Y-m-d H:i'),
                'Payé' => optional($i->paid_at)->format('Y-m-d H:i') ?? '',
            ])->all();

        return [['Référence', 'Objet', 'Montant', 'Moyen', 'Statut', 'Créé', 'Payé'], $rows];
    }

    private static function apAr(): array
    {
        $rows = [];
        foreach (Reservation::with('guest')->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->get() as $r) {
            if ($r->balance() > 0) {
                $rows[] = ['Type' => 'Créance', 'Tiers' => $r->guest->fullName(), 'Référence' => $r->reference, 'Montant' => $r->balance()];
            }
        }
        foreach (SupplierInvoice::with('supplier')->where('status', '!=', 'paid')->get() as $inv) {
            $rows[] = ['Type' => 'Dette', 'Tiers' => $inv->supplier->name ?? '—', 'Référence' => $inv->reference, 'Montant' => $inv->total - $inv->paid_amount];
        }

        return [['Type', 'Tiers', 'Référence', 'Montant'], $rows];
    }

    private static function fnbItems(Carbon $from, Carbon $to): array
    {
        $rows = OrderItem::whereHas('order', fn ($q) => $q
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled'))
            ->selectRaw('name, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('name')->orderByDesc('revenue')->get()
            ->map(fn ($i) => ['Article' => $i->name, 'Quantité' => (int) $i->qty, 'Chiffre d’affaires' => (int) $i->revenue])
            ->all();

        return [['Article', 'Quantité', 'Chiffre d’affaires'], $rows];
    }

    private static function posBestsellers(Carbon $from, Carbon $to): array
    {
        $rows = collect(Analytics::posBestsellers($from, $to))->map(fn ($b) => [
            'Article' => $b['name'],
            'Quantité' => $b['qty'],
            'Chiffre d’affaires' => $b['revenue'],
            'Coût matière' => $b['cost'],
            'Marge' => $b['margin'],
            'Marge %' => $b['margin_pct'] ?? '',
        ])->all();

        return [['Article', 'Quantité', 'Chiffre d’affaires', 'Coût matière', 'Marge', 'Marge %'], $rows];
    }

    private static function posServers(Carbon $from, Carbon $to): array
    {
        $rows = collect(Analytics::posServers($from, $to))->map(fn ($s) => [
            'Serveur' => $s['server'],
            'Tickets' => $s['tickets'],
            'Chiffre d’affaires' => $s['revenue'],
            'Remises' => $s['discounts'],
            'Annulations' => $s['cancelled'],
            'Remboursements' => $s['refunded'],
        ])->all();

        return [['Serveur', 'Tickets', 'Chiffre d’affaires', 'Remises', 'Annulations', 'Remboursements'], $rows];
    }

    private static function stockValuation(): array
    {
        $rows = StockItem::with('category')->where('is_active', true)->get()
            ->map(fn (StockItem $i) => [
                'Article' => $i->name,
                'Catégorie' => $i->category->name ?? '—',
                'Stock' => round($i->onHand(), 2),
                'CUMP' => $i->avg_cost,
                'Valeur' => (int) $i->stockValue(),
                'Sous seuil' => $i->isBelowThreshold() ? 'oui' : '',
            ])->all();

        return [['Article', 'Catégorie', 'Stock', 'CUMP', 'Valeur', 'Sous seuil'], $rows];
    }

    private static function loyaltyMembers(Carbon $from, Carbon $to): array
    {
        $rows = LoyaltyAccount::with('guest', 'tier')
            ->whereBetween('enrolled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(fn (LoyaltyAccount $a) => [
                'Carte' => $a->member_no,
                'Client' => $a->guest->fullName(),
                'Palier' => $a->tier->name ?? '—',
                'Solde points' => $a->points_balance,
                'Cumul à vie' => $a->lifetime_points,
                'Inscription' => optional($a->enrolled_at)->format('Y-m-d'),
            ])->all();

        return [['Carte', 'Client', 'Palier', 'Solde points', 'Cumul à vie', 'Inscription'], $rows];
    }

    private static function eventsPipeline(): array
    {
        $rows = EventLead::with('owner')->orderByDesc('estimated_value')->get()
            ->map(fn (EventLead $l) => [
                'Référence' => $l->reference,
                'Société' => $l->company ?: $l->contact_name,
                'Type' => $l->typeLabel(),
                'Étape' => $l->statusLabel(),
                'Pax' => $l->pax,
                'Budget estimé' => $l->estimated_value,
                'Référent' => $l->owner->name ?? '—',
            ])->all();

        return [['Référence', 'Société', 'Type', 'Étape', 'Pax', 'Budget estimé', 'Référent'], $rows];
    }
}
