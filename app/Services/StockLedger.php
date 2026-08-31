<?php

namespace App\Services;

use App\Models\StockItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mouvements de stock (§37) : chaque entrée / sortie passe par ici,
 * met à jour le niveau par magasin et le coût unitaire moyen pondéré.
 */
class StockLedger
{
    private const RECOST_REASONS = ['purchase_receipt', 'initial', 'return'];

    /**
     * @param  array{reason?:string,unit_cost?:int,reference?:string,transfer_group?:string,
     *   source?:Model,note?:string,allow_negative?:bool}  $opts
     */
    public static function move(StockItem $item, Warehouse $warehouse, string $direction, float $qty, array $opts = []): StockMovement
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La quantité doit être positive.']);
        }

        return DB::transaction(function () use ($item, $warehouse, $direction, $qty, $opts) {
            $level = StockLevel::query()
                ->where('stock_item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first()
                ?? new StockLevel(['stock_item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'quantity' => 0]);

            $current = (float) $level->quantity;

            if ($direction === 'out' && $current < $qty && ! ($opts['allow_negative'] ?? false)) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant pour « {$item->name} » dans {$warehouse->name} "
                        .'(disponible : '.rtrim(rtrim(number_format($current, 3, ',', ' '), '0'), ',').').',
                ]);
            }

            $unitCost = $opts['unit_cost'] ?? $item->avg_cost;
            $reason = $opts['reason'] ?? 'consumption';

            // Coût unitaire moyen pondéré, uniquement sur une vraie entrée valorisée.
            if ($direction === 'in' && in_array($reason, self::RECOST_REASONS, true)) {
                $before = $item->onHand();
                $after = $before + $qty;
                if ($after > 0) {
                    $item->avg_cost = (int) round((($before * $item->avg_cost) + ($qty * $unitCost)) / $after);
                    $item->save();
                }
            }

            $level->quantity = $current + ($direction === 'in' ? $qty : -$qty);
            $level->save();

            $source = $opts['source'] ?? null;

            return StockMovement::create([
                'hotel_id' => $item->hotel_id,
                'stock_item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => Auth::id(),
                'direction' => $direction,
                'reason' => $reason,
                'quantity' => $qty,
                'unit_cost' => $unitCost,
                'reference' => $opts['reference'] ?? null,
                'transfer_group' => $opts['transfer_group'] ?? null,
                'source_type' => $source?->getMorphClass(),
                'source_id' => $source?->getKey(),
                'note' => $opts['note'] ?? null,
            ]);
        });
    }

    public static function transfer(StockItem $item, Warehouse $from, Warehouse $to, float $qty, ?string $note = null): string
    {
        $group = (string) Str::uuid();

        DB::transaction(function () use ($item, $from, $to, $qty, $note, $group) {
            self::move($item, $from, 'out', $qty, ['reason' => 'transfer', 'transfer_group' => $group, 'note' => $note]);
            self::move($item, $to, 'in', $qty, ['reason' => 'transfer', 'transfer_group' => $group, 'note' => $note]);
        });

        return $group;
    }

    /** Aligne le stock d'un magasin sur une quantité comptée (inventaire). */
    public static function setLevel(StockItem $item, Warehouse $warehouse, float $countedQty, ?Model $source = null): ?StockMovement
    {
        $level = StockLevel::firstOrNew(['stock_item_id' => $item->id, 'warehouse_id' => $warehouse->id]);
        $current = (float) ($level->quantity ?? 0);
        $delta = $countedQty - $current;

        if (abs($delta) < 0.0005) {
            return null;
        }

        return self::move(
            $item, $warehouse,
            $delta > 0 ? 'in' : 'out',
            abs($delta),
            ['reason' => 'inventory', 'source' => $source, 'allow_negative' => true, 'note' => 'Ajustement inventaire'],
        );
    }
}
