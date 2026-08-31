<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Hotel;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceTicket;
use App\Models\Room;
use App\Models\User;
use App\Services\HousekeepingPlanner;
use App\Services\PreventiveMaintenance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        $tech = User::updateOrCreate(
            ['email' => 'maintenance@residence-khadija.sn'],
            [
                'name' => 'Technicien maintenance',
                'role' => 'maintenance',
                'job_title' => 'Technicien polyvalent',
                'hotel_id' => $hotel->id,
                'is_active' => true,
                'password' => Hash::make('khadija'),
            ],
        );

        // Un deuxième agent d'étage.
        User::updateOrCreate(
            ['email' => 'etage@residence-khadija.sn'],
            [
                'name' => 'Agent d’étage',
                'role' => 'housekeeping',
                'job_title' => 'Femme de chambre',
                'hotel_id' => $hotel->id,
                'is_active' => true,
                'password' => Hash::make('khadija'),
            ],
        );

        $this->seedEquipment($hotel);
        $this->seedPlans($hotel, $tech);

        // Génère les plans dus (échéances passées) puis les tâches de ménage du jour.
        PreventiveMaintenance::run();
        HousekeepingPlanner::generateForDate($hotel, Carbon::today());

        $this->seedDemoTickets($hotel, $tech);
    }

    private function seedEquipment(Hotel $hotel): void
    {
        if (Equipment::where('hotel_id', $hotel->id)->exists()) {
            return;
        }

        $items = [
            ['Ascenseur principal', 'ascenseur', 'Hall — cage A'],
            ['Ascenseur de service', 'ascenseur', 'Office — cage B'],
            ['Groupe électrogène 250 kVA', 'groupe_electrogene', 'Local technique RDC'],
            ['Onduleur salle serveurs', 'electricite', 'Local IT'],
            ['Tableau général basse tension', 'electricite', 'Local technique RDC'],
            ['Centrale de traitement d’air', 'climatisation', 'Toiture'],
            ['Split restaurant Teranga', 'climatisation', 'Restaurant'],
            ['Split salle Ndar', 'climatisation', 'Étage séminaire'],
            ['Pompe de filtration piscine', 'piscine', 'Local piscine'],
            ['Traitement automatique piscine', 'piscine', 'Local piscine'],
            ['Chambre froide positive', 'cuisine', 'Cuisine principale'],
            ['Chambre froide négative', 'cuisine', 'Cuisine principale'],
            ['Hotte et extraction cuisine', 'cuisine', 'Cuisine principale'],
            ['Tapis de course x3', 'fitness', 'Salle fitness'],
            ['Presse à charge guidée', 'fitness', 'Salle fitness'],
            ['Surpresseur eau sanitaire', 'plomberie', 'Local technique RDC'],
        ];

        foreach ($items as [$name, $category, $location]) {
            Equipment::create([
                'hotel_id' => $hotel->id,
                'name' => $name,
                'category' => $category,
                'location' => $location,
                'status' => 'operational',
                'commissioned_on' => now()->subYears(2)->subMonths(random_int(0, 20)),
            ]);
        }
    }

    private function seedPlans(Hotel $hotel, User $tech): void
    {
        foreach (config('maintenance.preventive_plans') as $p) {
            $equipment = Equipment::where('hotel_id', $hotel->id)
                ->where('category', $p['category'])->first();

            MaintenancePlan::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $p['name']],
                [
                    'equipment_category' => $p['category'],
                    'equipment_id' => $equipment?->id,
                    'interval_days' => $p['interval_days'],
                    'checklist' => $p['checklist'],
                    'priority' => $p['priority'],
                    'assigned_to' => $tech->id,
                    // Certaines échéances dans le passé → généreront un ticket dès le seed.
                    'next_due_on' => now()->subDays(random_int(0, (int) ($p['interval_days'] / 2)))->toDateString(),
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedDemoTickets(Hotel $hotel, User $tech): void
    {
        if (MaintenanceTicket::where('type', 'corrective')->exists()) {
            return;
        }

        $room = Room::where('hotel_id', $hotel->id)->inRandomOrder()->first();
        $split = Equipment::where('category', 'climatisation')->first();

        MaintenanceTicket::create([
            'reference' => PreventiveMaintenance::reference(),
            'hotel_id' => $hotel->id,
            'room_id' => $room?->id,
            'reported_by' => $tech->id,
            'type' => 'corrective',
            'priority' => 'high',
            'status' => 'in_progress',
            'title' => 'Fuite robinet salle de bain',
            'description' => 'Goutte à goutte au niveau du mitigeur, joint à remplacer.',
            'assigned_to' => $tech->id,
            'started_at' => now()->subHours(3),
        ]);

        MaintenanceTicket::create([
            'reference' => PreventiveMaintenance::reference(),
            'hotel_id' => $hotel->id,
            'equipment_id' => $split?->id,
            'reported_by' => $tech->id,
            'type' => 'corrective',
            'priority' => 'critical',
            'status' => 'open',
            'title' => 'Climatisation restaurant en panne',
            'description' => 'Ne refroidit plus, disjoncte au démarrage.',
        ]);

        MaintenanceTicket::create([
            'reference' => PreventiveMaintenance::reference(),
            'hotel_id' => $hotel->id,
            'reported_by' => $tech->id,
            'type' => 'corrective',
            'priority' => 'normal',
            'status' => 'closed',
            'title' => 'Ampoule couloir 2e étage',
            'description' => 'Remplacement spot LED.',
            'assigned_to' => $tech->id,
            'resolution' => 'Spot LED 7W remplacé.',
            'parts_cost' => 3500,
            'labor_cost' => 2000,
            'started_at' => now()->subDay(),
            'resolved_at' => now()->subDay()->addHour(),
            'closed_at' => now()->subDay()->addHours(2),
        ]);
    }
}
