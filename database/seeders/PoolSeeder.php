<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\PoolAsset;
use App\Models\Reservation;
use App\Services\PoolBooking;
use Illuminate\Database\Seeder;

class PoolSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        if (PoolAsset::where('hotel_id', $hotel->id)->exists()) {
            return;
        }

        $order = 0;
        foreach (config('pool.seed_assets') as $group) {
            for ($i = 1; $i <= $group['count']; $i++) {
                PoolAsset::create([
                    'hotel_id' => $hotel->id,
                    'kind' => $group['kind'],
                    'label' => $group['prefix'].' '.$i,
                    'capacity' => $group['capacity'],
                    'half_day_price' => $group['half_day_price'],
                    'full_day_price' => $group['full_day_price'],
                    'is_active' => true,
                    'sort_order' => $order++,
                ]);
            }
        }

        // Quelques réservations de démonstration pour aujourd'hui.
        $assets = PoolAsset::where('hotel_id', $hotel->id)->orderBy('id')->get();
        $stays = Reservation::where('status', 'checked_in')->with('guest')->take(2)->get();

        $demo = [
            [$assets->firstWhere('kind', 'transat'), 'full_day', 'M. Diallo', 1],
            [$assets->where('kind', 'transat')->skip(1)->first(), 'morning', 'Famille Ndiaye', 1],
            [$assets->firstWhere('kind', 'cabana'), 'afternoon', $stays->first()?->guest->fullName() ?? 'Mme Sarr', 4],
        ];

        foreach ($demo as [$asset, $slot, $name, $pax]) {
            if (! $asset) {
                continue;
            }
            PoolBooking::book([
                'pool_asset_id' => $asset->id,
                'date' => today()->toDateString(),
                'slot' => $slot,
                'guest_name' => $name,
                'guests' => $pax,
                'reservation_id' => $slot === 'afternoon' ? $stays->first()?->id : null,
            ]);
        }
    }
}
