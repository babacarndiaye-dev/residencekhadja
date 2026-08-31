<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuOption;
use App\Models\MenuOptionGroup;
use App\Models\QrLocation;
use App\Models\Room;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DiningSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        User::updateOrCreate(
            ['email' => 'restaurant@residence-khadija.sn'],
            [
                'name' => 'Restauration',
                'role' => 'restaurant',
                'job_title' => 'Maître d’hôtel',
                'hotel_id' => $hotel->id,
                'is_active' => true,
                'password' => Hash::make('khadija'),
            ],
        );

        // Points de vente (depuis config/dining.php) + Room Service.
        $venues = [];
        foreach (array_values(config('dining.venues')) as $i => $v) {
            $venues[$v['slug']] = Venue::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $v['slug']],
                [
                    'name' => $v['name'],
                    'type' => $v['type'],
                    'hours' => $v['hours'],
                    'accepts_qr_orders' => true,
                    'is_room_service' => false,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }
        $roomService = Venue::updateOrCreate(
            ['hotel_id' => $hotel->id, 'slug' => 'room-service'],
            [
                'name' => 'Room Service',
                'type' => 'Service en chambre',
                'hours' => '24h/24',
                'accepts_qr_orders' => true,
                'is_room_service' => true,
                'is_active' => true,
                'sort_order' => 90,
            ],
        );

        // Carte : catégories → items → groupes d’options → options.
        foreach (config('menu.categories') as $ci => $cat) {
            $category = MenuCategory::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'] ?? null,
                    'room_service' => $cat['room_service'] ?? false,
                    'needs_kitchen' => $cat['kitchen'] ?? true,
                    'is_active' => true,
                    'sort_order' => $ci,
                ],
            );

            $venueIds = collect($cat['venues'] ?? [])
                ->map(fn ($slug) => $venues[$slug]->id ?? null)
                ->filter()->all();
            if ($cat['room_service'] ?? false) {
                $venueIds[] = $roomService->id;
            }
            $category->venues()->sync($venueIds);

            foreach (array_values($cat['items']) as $ii => $item) {
                $model = MenuItem::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'slug' => $item['slug']],
                    [
                        'menu_category_id' => $category->id,
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'story' => $item['story'] ?? null,
                        'price' => $item['price'],
                        'image' => $item['image'] ?? null,
                        'allergens' => $item['allergens'] ?? [],
                        'tags' => $item['tags'] ?? [],
                        'is_available' => true,
                        'is_signature' => $item['signature'] ?? false,
                        'sort_order' => $ii,
                    ],
                );

                $model->optionGroups()->delete();
                foreach (($item['options'] ?? []) as $gi => $group) {
                    $g = MenuOptionGroup::create([
                        'menu_item_id' => $model->id,
                        'name' => $group['name'],
                        'type' => $group['type'] ?? 'single',
                        'required' => $group['required'] ?? false,
                        'sort_order' => $gi,
                    ]);
                    foreach (array_values($group['choices']) as $oi => $choice) {
                        MenuOption::create([
                            'menu_option_group_id' => $g->id,
                            'name' => $choice['name'],
                            'price_delta' => $choice['price_delta'] ?? 0,
                            'sort_order' => $oi,
                        ]);
                    }
                }
            }
        }

        // QR : tables par lieu + une localisation room service par chambre.
        $zoneByVenue = [
            'restaurant-teranga' => 'Salle',
            'rooftop-baobab' => 'Rooftop',
            'lobby-lounge' => 'Lounge',
            'pool-bar' => 'Piscine',
        ];
        foreach (config('menu.tables_per_venue') as $slug => $count) {
            $venue = $venues[$slug] ?? null;
            if (! $venue) {
                continue;
            }
            $prefix = strtoupper(Str::of($slug)->explode('-')->map(fn ($p) => $p[0])->join(''));
            for ($t = 1; $t <= $count; $t++) {
                QrLocation::updateOrCreate(
                    ['code' => "TBL-{$prefix}-".str_pad((string) $t, 2, '0', STR_PAD_LEFT)],
                    [
                        'hotel_id' => $hotel->id,
                        'venue_id' => $venue->id,
                        'label' => "Table {$t}",
                        'zone' => $zoneByVenue[$slug] ?? null,
                        'type' => 'table',
                        'seats' => 4,
                        'is_active' => true,
                    ],
                );
            }
        }

        foreach (Room::where('hotel_id', $hotel->id)->get() as $room) {
            QrLocation::updateOrCreate(
                ['code' => "ROOM-{$room->number}"],
                [
                    'hotel_id' => $hotel->id,
                    'venue_id' => $roomService->id,
                    'room_id' => $room->id,
                    'label' => "Chambre {$room->number}",
                    'type' => 'room',
                    'is_active' => true,
                ],
            );
        }
    }
}
