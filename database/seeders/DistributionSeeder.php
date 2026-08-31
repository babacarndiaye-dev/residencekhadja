<?php

namespace Database\Seeders;

use App\Models\AvailabilityDay;
use App\Models\Channel;
use App\Models\ChannelRatePlan;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Services\ChannelManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        /* ----------------------------- Canaux ---------------------------- */
        foreach (config('distribution.seed_channels') as $c) {
            Channel::updateOrCreate(
                ['hotel_id' => $hotel->id, 'key' => $c['key']],
                [
                    'name' => $c['name'],
                    'type' => $c['type'],
                    'connector' => $c['connector'],
                    'commission_rate' => $c['commission_rate'],
                    'currency' => 'XOF',
                    'is_active' => true,
                ],
            );
        }

        /* ----------------------- Mapping tarifaire ---------------------- */
        $plans = RatePlan::where('hotel_id', $hotel->id)->get();
        foreach (Channel::where('key', '!=', 'direct')->get() as $channel) {
            foreach ($plans as $plan) {
                ChannelRatePlan::updateOrCreate(
                    ['channel_id' => $channel->id, 'rate_plan_id' => $plan->id],
                    [
                        // Booking.com : +8 % pour absorber la commission ; les autres en parité.
                        'markup_rate' => $channel->key === 'booking_com' ? 0.08 : 0.0,
                        'external_code' => strtoupper($channel->key).'-'.strtoupper($plan->key),
                        'is_active' => true,
                    ],
                );
            }
        }

        /* ------------------ Restrictions de calendrier ----------------- */
        if (AvailabilityDay::count() === 0) {
            $category = RoomCategory::active()->orderByDesc('price')->first();
            // Stop-sell sur la suite la plus chère pour 2 nuits, dans 3 semaines.
            foreach ([21, 22] as $offset) {
                AvailabilityDay::create([
                    'hotel_id' => $hotel->id,
                    'room_category_id' => $category->id,
                    'date' => Carbon::today()->addDays($offset)->toDateString(),
                    'stop_sell' => true,
                    'note' => 'Blocage propriétaire',
                ]);
            }
            // Séjour minimum 2 nuits le week-end prochain sur une catégorie standard.
            $std = RoomCategory::active()->orderBy('price')->first();
            $friday = Carbon::today()->next(Carbon::FRIDAY);
            foreach ([0, 1] as $i) {
                AvailabilityDay::create([
                    'hotel_id' => $hotel->id,
                    'room_category_id' => $std->id,
                    'date' => $friday->copy()->addDays($i)->toDateString(),
                    'min_stay' => 2,
                ]);
            }
        }

        /* ------------------ Réservations entrantes démo --------------- */
        if (Reservation::where('channel', '!=', 'direct')->doesntExist()) {
            $cat = RoomCategory::active()->orderBy('sort_order')->first();

            $samples = [
                ['booking_com', 'Lena', 'Schmidt', -6, 3, 240000, true],   // séjour passé → check-out + commission
                ['expedia', 'Marco', 'Rossi', 4, 2, 180000, false],
                ['airbnb', 'Yuki', 'Tanaka', 12, 4, 320000, false],
            ];

            foreach ($samples as [$channelKey, $first, $last, $inOffset, $nights, $gross, $past]) {
                $channel = Channel::where('key', $channelKey)->first();
                $in = Carbon::today()->addDays($inOffset);

                try {
                    $cr = ChannelManager::ingestReservation($channel, [
                        'external_ref' => strtoupper($channelKey).'-DEMO-'.$first,
                        'first_name' => $first, 'last_name' => $last,
                        'room_slug' => $cat->slug,
                        'check_in' => $in->toDateString(),
                        'check_out' => $in->copy()->addDays($nights)->toDateString(),
                        'adults' => 2, 'gross_amount' => $gross,
                    ]);
                } catch (\Throwable) {
                    continue;
                }

                if ($past && $cr->reservation) {
                    $cr->reservation->update([
                        'status' => 'checked_out',
                        'checked_in_at' => $in,
                        'checked_out_at' => $in->copy()->addDays($nights),
                    ]);
                }
            }
        }

        /* -------------------------- Poussée initiale ------------------- */
        ChannelManager::pushAvailability(Carbon::today(), Carbon::today()->addDays(30));
    }
}
