<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\BookingQuote;
use App\Support\Notify;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::updateOrCreate(
            ['slug' => 'residence-khadija-thies'],
            [
                'name' => config('hotel.name'),
                'city' => config('hotel.city'),
                'country' => config('hotel.country'),
                'address' => config('hotel.contact.address_line'),
                'phone' => config('hotel.contact.phone'),
                'email' => config('hotel.contact.email'),
                'currency' => 'XOF',
                'is_active' => true,
            ],
        );

        // Plans tarifaires (depuis config/booking.php).
        $order = 0;
        foreach (config('booking.rate_plans') as $key => $plan) {
            RatePlan::updateOrCreate(
                ['hotel_id' => $hotel->id, 'key' => $key],
                [
                    'name' => $plan['label'],
                    'multiplier' => $plan['multiplier'],
                    'note' => $plan['note'],
                    'is_refundable' => $key === 'flexible',
                    'is_active' => true,
                    'sort_order' => $order++,
                ],
            );
        }

        // Catégories de chambre (depuis config/rooms.php) + chambres physiques.
        foreach (array_values(config('rooms.items')) as $i => $item) {
            $category = RoomCategory::updateOrCreate(
                ['hotel_id' => $hotel->id, 'slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'price' => $item['price'],
                    'size' => $item['size'],
                    'capacity' => $item['capacity'],
                    'bed' => $item['bed'],
                    'view' => $item['view'],
                    'short' => $item['short'],
                    'description' => $item['description'],
                    'amenities' => $item['amenities'],
                    'images' => $item['images'],
                    'featured' => $item['featured'],
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );

            // ~8 à 16 chambres par catégorie, réparties sur les étages 1 à 4.
            $count = [12, 16, 10, 6, 4][$i] ?? 8;
            for ($n = 1; $n <= $count; $n++) {
                $floor = (int) ceil($n / 4);
                $number = ($i + 1) * 100 + $n;   // 101.. / 201.. / 301..
                Room::updateOrCreate(
                    ['hotel_id' => $hotel->id, 'number' => (string) $number],
                    [
                        'room_category_id' => $category->id,
                        'floor' => (string) $floor,
                        'building' => 'Principal',
                        'status' => 'propre',
                        'is_active' => true,
                    ],
                );
            }
        }

        // Comptes staff (mot de passe : « khadija »).
        $staff = [
            ['name' => 'Direction Khadija', 'email' => 'direction@residence-khadija.sn', 'role' => 'direction', 'job_title' => 'Directeur général', 'pos_pin' => '2468'],
            ['name' => 'Réception', 'email' => 'reception@residence-khadija.sn', 'role' => 'reception', 'job_title' => 'Réceptionniste'],
            ['name' => 'Gouvernante', 'email' => 'housekeeping@residence-khadija.sn', 'role' => 'housekeeping', 'job_title' => 'Gouvernante générale'],
            ['name' => 'Administrateur', 'email' => 'admin@residence-khadija.sn', 'role' => 'admin', 'job_title' => 'Administrateur système', 'pos_pin' => '1379'],
        ];
        foreach ($staff as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'role' => $s['role'],
                    'job_title' => $s['job_title'],
                    'hotel_id' => $hotel->id,
                    'is_active' => true,
                    'password' => Hash::make('khadija'),
                    'pos_pin' => $s['pos_pin'] ?? null,
                ],
            );
        }

        $this->seedDemoReservations($hotel);
        $this->seedDemoContactMessages();
        $this->seedDemoNotifications();

        $this->call([
            DiningSeeder::class,
            OperationsSeeder::class,
            EconomySeeder::class,
            WorkforceSeeder::class,
            CrmSeeder::class,
            EventsSeeder::class,
            PaymentsSeeder::class,
            DistributionSeeder::class,
            GuestAppSeeder::class,
            PoolSeeder::class,
            SatisfactionSeeder::class,
            BiSeeder::class,
        ]);

        // PIN caisse (identification légère au comptoir) pour tous les rôles POS.
        User::where('is_active', true)
            ->whereIn('role', config('pos.server_roles', ['restaurant', 'reception', 'direction']))
            ->whereNull('pos_pin')
            ->get()
            ->each(fn (User $u) => $u->update(['pos_pin' => str_pad((string) (1000 + $u->id % 9000), 4, '0', STR_PAD_LEFT)]));
    }

    /** Deux notifications in-app de démonstration pour la réception. */
    private function seedDemoNotifications(): void
    {
        $reception = User::where('role', 'reception')->first();
        if (! $reception || $reception->notifications()->exists()) {
            return;
        }

        Notify::users(
            [$reception],
            'Nouveau message de contact',
            'Fatou Sarr — Demande d\'information',
            route('admin.messages.index', absolute: false),
            icon: '✉️',
        );
        Notify::users(
            [$reception],
            'Demande client — chambre 204',
            'Serviettes supplémentaires',
            route('admin.guest_requests.index', absolute: false),
            level: 'warning',
            icon: '🛎️',
        );
    }

    /** Quelques messages de contact de démonstration. */
    private function seedDemoContactMessages(): void
    {
        if (ContactMessage::query()->exists()) {
            return;
        }

        $samples = [
            ['Fatou Sarr', 'fatou.sarr@example.sn', '+221 77 123 45 67', 'Demande d\'information',
                "Bonjour,\n\nNous souhaitons organiser un séminaire de 30 personnes en octobre. Proposez-vous des forfaits journée d'étude avec déjeuner ?\n\nMerci.", 'new'],
            ['Pierre Lemoine', 'pierre.lemoine@example.fr', null, 'Réservation de groupe',
                'Bonjour, je prépare un voyage pour 12 personnes fin novembre. Est-il possible de bloquer 6 chambres doubles ? Cordialement.', 'read'],
            ['Awa Ndiaye', 'awa.ndiaye@example.sn', '+221 76 000 11 22', 'Objet trouvé',
                "J'ai peut-être oublié une écharpe dans la chambre 204 après mon départ le week-end dernier. Pouvez-vous vérifier ? Merci beaucoup.", 'handled'],
        ];

        foreach ($samples as [$name, $email, $phone, $subject, $body, $status]) {
            ContactMessage::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $body,
                'status' => $status,
                'handled_by' => $status === 'handled' ? User::where('role', 'reception')->value('id') : null,
                'handled_at' => $status === 'handled' ? now()->subDay() : null,
                'created_at' => now()->subDays(array_search($status, ['new', 'read', 'handled'], true) + 1),
                'updated_at' => now()->subDays(array_search($status, ['new', 'read', 'handled'], true) + 1),
            ]);
        }
    }

    /** Quelques réservations de démonstration autour de la date du jour. */
    private function seedDemoReservations(Hotel $hotel): void
    {
        if (Reservation::query()->exists()) {
            return;
        }

        $categories = RoomCategory::where('hotel_id', $hotel->id)->get();
        $ratePlans = RatePlan::where('hotel_id', $hotel->id)->pluck('id', 'key');

        $samples = [
            ['Aminata', 'Diop', 'aminata.diop@example.sn', 'Sénégal', -1, 3, 'checked_in'],
            ['Jean', 'Martin', 'jean.martin@example.fr', 'France', 0, 2, 'confirmed'],
            ['Ousmane', 'Ba', 'ousmane.ba@example.sn', 'Sénégal', 0, 4, 'confirmed'],
            ['Clara', 'Nunes', 'clara.nunes@example.pt', 'Portugal', 1, 2, 'pending'],
            ['Modou', 'Fall', 'modou.fall@example.sn', 'Sénégal', -3, 1, 'checked_out'],
            ['Sarah', 'Cohen', 'sarah.cohen@example.com', 'Canada', 2, 5, 'confirmed'],
            ['Ibrahima', 'Sow', 'ibrahima.sow@example.sn', 'Sénégal', -2, 6, 'checked_in'],
        ];

        foreach ($samples as [$first, $last, $email, $country, $offsetIn, $nights, $status]) {
            $category = $categories->random();
            $checkIn = Carbon::today()->addDays($offsetIn);
            $checkOut = $checkIn->copy()->addDays($nights);

            $guest = Guest::updateOrCreate(
                ['email' => $email],
                ['civility' => 'M.', 'first_name' => $first, 'last_name' => $last, 'phone' => '+221 77 000 00 00', 'country' => $country],
            );

            $bookingState = [
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'adults' => 2, 'children' => 0, 'rooms' => 1,
                'room_slug' => $category->slug, 'rate_plan' => 'flexible',
                'extras' => [], 'promo' => null,
            ];
            $quote = BookingQuote::for($bookingState);

            $reservation = Reservation::create([
                'reference' => 'HRK-'.strtoupper(Str::random(6)),
                'hotel_id' => $hotel->id,
                'guest_id' => $guest->id,
                'room_category_id' => $category->id,
                'rate_plan_id' => $ratePlans['flexible'] ?? null,
                'status' => $status,
                'channel' => 'direct',
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => 2,
                'rooms_count' => 1,
                'room_total' => $quote['room_total'],
                'tax_amount' => $quote['tax'],
                'tourist_tax' => $quote['tourist_tax'],
                'total' => $quote['total'],
                'deposit' => $quote['deposit'],
                'confirmed_at' => in_array($status, ['confirmed', 'checked_in', 'checked_out'], true) ? now() : null,
                'checked_in_at' => in_array($status, ['checked_in', 'checked_out'], true) ? $checkIn : null,
                'checked_out_at' => $status === 'checked_out' ? $checkOut : null,
            ]);

            // Affecte une chambre physique aux séjours en cours / terminés.
            if (in_array($status, ['checked_in', 'checked_out'], true)) {
                $room = Room::where('room_category_id', $category->id)
                    ->where('status', 'propre')->first();
                if ($room) {
                    $reservation->update(['room_id' => $room->id]);
                    $room->update(['status' => $status === 'checked_in' ? 'occupee' : 'sale']);
                }
            }
        }
    }
}
