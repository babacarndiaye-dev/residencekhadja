<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\SatisfactionSurvey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SatisfactionSeeder extends Seeder
{
    public function run(): void
    {
        if (SatisfactionSurvey::query()->exists()) {
            return;
        }

        $hotelId = Hotel::query()->value('id');
        $guests = Guest::query()->inRandomOrder()->limit(24)->get();
        if (! $hotelId || $guests->isEmpty()) {
            return;
        }

        $comments = [
            5 => [
                'Séjour parfait : accueil chaleureux, chambre impeccable et rooftop magnifique au coucher du soleil.',
                'La Teranga à l’état pur. Le personnel se souvenait de nos prénoms. On revient sans hésiter.',
                'Petit-déjeuner généreux, lit très confortable, check-in en deux minutes avant ma réunion.',
            ],
            4 => [
                'Très bon séjour dans l’ensemble. Quelques bruits de couloir le soir, mais rien de grave.',
                'Chambre confortable et propre, le wifi ralentit un peu en soirée.',
                'Bon rapport qualité-prix, piscine idéale pour les enfants.',
            ],
            3 => [
                'Correct sans plus. La climatisation était bruyante et l’attente au check-in un peu longue.',
                'Chambre propre mais accueil en retrait à l’arrivée.',
            ],
            2 => [
                'Chambre pas prête à l’heure du check-in et douche capricieuse. Dommage.',
                'Déçu par le petit-déjeuner et par l’attente à la réception le matin du départ.',
            ],
            1 => [
                'Nuit gâchée par des travaux dès 7 h et personne pour nous proposer une solution.',
            ],
        ];
        $npsFor = [1 => 2, 2 => 4, 3 => 6, 4 => 8, 5 => 10];
        $cities = ['Dakar', 'Thiès', 'Saint-Louis', 'Abidjan', 'Paris', 'Bamako', 'Sénégal'];

        // [jours écoulés, note globale, complété ?, accord publication ?, publié ?]
        $plan = [
            [3, 5, true, true, true], [6, 4, true, true, true], [9, 5, true, true, true],
            [12, 2, true, false, false], [15, 4, true, true, false], [18, 3, true, true, false],
            [22, 5, true, true, true], [26, 1, true, false, false], [30, 4, true, true, false],
            [35, 5, true, true, true], [40, 3, true, false, false], [45, 4, true, true, false],
            [52, 5, true, true, false], [60, 2, true, true, false], [70, 4, true, true, false],
            [80, 5, true, true, false],
            [1, null, false, false, false], [2, null, false, false, false],
        ];

        foreach ($plan as $i => [$daysAgo, $overall, $done, $consent, $published]) {
            $guest = $guests[$i % $guests->count()];
            $sentAt = Carbon::now()->subDays($daysAgo + 1);
            $completedAt = $done ? Carbon::now()->subDays($daysAgo) : null;

            $cats = null;
            if ($done) {
                $cats = [];
                foreach (array_keys(config('satisfaction.categories')) as $k) {
                    $cats[$k] = max(1, min(5, $overall + random_int(-1, 1)));
                }
            }

            SatisfactionSurvey::create([
                'hotel_id' => $hotelId,
                'guest_id' => $guest->id,
                'token' => Str::lower(Str::random(40)),
                'channel' => 'post_stay',
                'sent_at' => $sentAt,
                'expires_on' => $sentAt->copy()->addDays(30)->toDateString(),
                'completed_at' => $completedAt,
                'rating_overall' => $overall,
                'nps_score' => $done ? $npsFor[$overall] : null,
                'category_ratings' => $cats,
                'comment' => $done ? $comments[$overall][array_rand($comments[$overall])] : null,
                'consent_publish' => $consent,
                'author_label' => $consent
                    ? $guest->first_name.' '.Str::substr((string) $guest->last_name, 0, 1).'., '.$cities[array_rand($cities)]
                    : null,
                'status' => $done ? (($published || $overall >= 4) ? 'triaged' : 'received') : 'pending',
                'handled_at' => $done && ($published || $overall >= 4) ? $completedAt : null,
                'is_published' => $published,
                'published_at' => $published ? $completedAt : null,
                'created_at' => $sentAt,
                'updated_at' => $completedAt ?? $sentAt,
            ]);
        }
    }
}
