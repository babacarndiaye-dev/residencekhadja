<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\Analytics;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BiSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::current();

        // Instantanés quotidiens (tendances du tableau de bord). Fenêtre réduite en test.
        $window = app()->runningUnitTests() ? 5 : 45;
        for ($i = $window; $i >= 1; $i--) {
            Analytics::snapshot(Carbon::today()->subDays($i));
        }

        if (ReportSchedule::count() === 0) {
            $direction = User::where('role', 'direction')->first();
            ReportSchedule::insert([
                [
                    'hotel_id' => $hotel->id, 'report_key' => 'revenue_daily', 'frequency' => 'weekly',
                    'recipients' => json_encode(['direction@residence-khadija.sn']), 'range_days' => 7,
                    'is_active' => true, 'created_by' => $direction?->id,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'hotel_id' => $hotel->id, 'report_key' => 'occupancy_daily', 'frequency' => 'monthly',
                    'recipients' => json_encode(['direction@residence-khadija.sn', 'finance@residence-khadija.sn']), 'range_days' => 31,
                    'is_active' => true, 'created_by' => $direction?->id,
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
        }
    }
}
