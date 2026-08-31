<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\LoyaltyTier;
use App\Models\MarketingCampaign;
use App\Models\MarketingSegment;
use App\Models\PromoCode;
use App\Models\User;
use App\Services\CampaignDispatcher;
use App\Services\LoyaltyProgram;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        /* --------------------------- Paliers --------------------------- */
        $order = 0;
        foreach (config('loyalty.tiers') as $tier) {
            LoyaltyTier::updateOrCreate(
                ['hotel_id' => $hotel->id, 'code' => $tier['code']],
                [
                    'name' => $tier['name'],
                    'min_points' => $tier['min_points'],
                    'earn_rate' => $tier['earn_rate'],
                    'perks' => $tier['perks'],
                    'color' => $tier['color'],
                    'sort_order' => $order++,
                ],
            );
        }

        /* ----------------------- Utilisateur marketing ---------------- */
        User::updateOrCreate(
            ['email' => 'marketing@residence-khadija.sn'],
            [
                'name' => 'Pôle CRM & Marketing',
                'role' => 'marketing',
                'job_title' => 'Responsable CRM',
                'hotel_id' => $hotel->id,
                'is_active' => true,
                'password' => Hash::make('khadija'),
            ],
        );

        /* -------------- Enrichissement des clients existants ---------- */
        $sources = array_keys(config('crm.acquisition_sources'));
        $tagPool = config('crm.tag_suggestions');

        Guest::query()->orderBy('id')->get()->each(function (Guest $guest, int $i) use ($sources, $tagPool) {
            $guest->forceFill([
                'birthdate' => $guest->birthdate ?: Carbon::create(1975 + ($i % 30), ($i % 12) + 1, (($i * 7) % 27) + 1),
                'marketing_opt_in' => $i % 10 !== 0,       // 90 % opt-in
                'consent_updated_at' => now()->subDays($i),
                'acquisition_source' => $sources[$i % count($sources)],
                'tags' => $i % 3 === 0 ? [$tagPool[$i % count($tagPool)]] : [],
            ])->save();

            $account = LoyaltyProgram::enroll($guest);

            $spend = $guest->lifetimeValue();
            if ($spend > 0) {
                LoyaltyProgram::earn($account, $spend, 'Reprise historique séjours');
            }
        });

        /* ---------------- Clients CRM de démonstration --------------- */
        $demo = [
            ['Fatou', 'Sarr', 'fatou.sarr@example.sn', 'Sénégal', true],
            ['Cheikh', 'Diouf', 'cheikh.diouf@example.sn', 'Sénégal', true],
            ['Marie', 'Leroy', 'marie.leroy@example.fr', 'France', true],
            ['Karim', 'Benali', 'karim.benali@example.ma', 'Maroc', false],
            ['Awa', 'Ndiaye', 'awa.ndiaye@example.sn', 'Sénégal', true],
            ['Paulo', 'Silva', 'paulo.silva@example.br', 'Brésil', true],
            ['Nafissatou', 'Cissé', 'nafi.cisse@example.sn', 'Sénégal', true],
            ['Thomas', 'Bauer', 'thomas.bauer@example.de', 'Allemagne', false],
        ];

        foreach ($demo as $k => [$first, $last, $email, $country, $optIn]) {
            $guest = Guest::updateOrCreate(
                ['email' => $email],
                [
                    'civility' => $k % 2 ? 'M.' : 'Mme',
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => '+221 77 '.random_int(100, 999).' '.random_int(10, 99).' '.random_int(10, 99),
                    'country' => $country,
                    // La moitié fête son anniversaire ce mois-ci (segment "anniversaires").
                    'birthdate' => $k % 2
                        ? now()->subYears(30 + $k)->setDay(min(28, $k + 3))
                        : Carbon::create(1980 + $k, (($k + 5) % 12) + 1, 12),
                    'marketing_opt_in' => $optIn,
                    'consent_updated_at' => now(),
                    'acquisition_source' => $sources[$k % count($sources)],
                    'tags' => $k % 2 ? ['Affaires'] : ['Famille'],
                ],
            );

            $account = LoyaltyProgram::enroll($guest);
            if ($k % 3 !== 0) {
                LoyaltyProgram::earn($account, random_int(2, 18) * 100000, 'Bonus bienvenue programme');
            }
        }

        /* ------------------------ Interactions ---------------------- */
        $agent = User::where('role', 'reception')->first() ?? User::first();
        Guest::query()->take(3)->get()->each(function (Guest $g) use ($agent, $hotel) {
            $g->interactions()->create([
                'hotel_id' => $hotel->id,
                'user_id' => $agent?->id,
                'type' => 'call',
                'subject' => 'Appel de courtoisie après séjour',
                'body' => 'Client satisfait, souhaite être recontacté pour un séminaire.',
                'occurred_at' => now()->subDays(random_int(2, 20)),
            ]);
        });

        /* -------------------------- Segments ----------------------- */
        $creator = User::where('role', 'marketing')->first();
        $segments = [
            ['Clients fidèles', 'Au moins 2 séjours honorés', ['min_stays' => 2, 'opted_in' => true]],
            ['Anniversaires du mois', 'Anniversaire dans le mois courant', ['birthday_month' => 'current', 'opted_in' => true]],
            ['À reconquérir', 'Sans séjour depuis plus de 180 jours', ['inactive_days' => 180, 'opted_in' => true]],
            ['Base opt-in complète', 'Tous les clients ayant consenti', ['opted_in' => true]],
        ];
        $segmentModels = [];
        foreach ($segments as [$name, $desc, $definition]) {
            $segmentModels[$name] = MarketingSegment::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $name],
                ['description' => $desc, 'definition' => $definition, 'created_by' => $creator?->id],
            );
        }

        /* ------------------------ Codes promo ---------------------- */
        $promo = PromoCode::updateOrCreate(
            ['hotel_id' => $hotel->id, 'code' => 'FIDELITE10'],
            ['type' => 'percent', 'value' => 10, 'label' => 'Fidélité -10 %', 'active' => true],
        );
        PromoCode::updateOrCreate(
            ['hotel_id' => $hotel->id, 'code' => 'REVENIR12'],
            ['type' => 'percent', 'value' => 12, 'label' => 'Offre retour -12 %', 'active' => true,
                'ends_on' => now()->addMonths(3)->toDateString()],
        );

        /* ------------------------- Campagnes ----------------------- */
        if (MarketingCampaign::count() === 0) {
            $sent = MarketingCampaign::create([
                'hotel_id' => $hotel->id,
                'name' => 'Bienvenue au programme Privilège',
                'channel' => 'email',
                'segment_id' => $segmentModels['Base opt-in complète']->id,
                'promo_code_id' => $promo->id,
                'subject' => 'Votre carte Khadija Privilège vous attend',
                'body' => "Bonjour {prenom},\n\nMerci de votre fidélité. Profitez de -10 % sur votre prochain séjour avec le code {code}.",
                'status' => 'draft',
                'created_by' => $creator?->id,
            ]);
            CampaignDispatcher::send($sent);

            MarketingCampaign::create([
                'hotel_id' => $hotel->id,
                'name' => 'Relance anniversaires',
                'channel' => 'email',
                'segment_id' => $segmentModels['Anniversaires du mois']->id,
                'promo_code_id' => $promo->id,
                'subject' => 'Un cadeau pour votre anniversaire 🎁',
                'body' => "Bonjour {prenom},\n\nJoyeux anniversaire ! -15 % avec le code {code}.",
                'status' => 'draft',
                'created_by' => $creator?->id,
            ]);
        }
    }
}
