<?php

namespace Tests\Feature;

use App\Mail\ScheduledReport;
use App\Models\DailyMetric;
use App\Models\FinanceAccount;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\Analytics;
use App\Services\FinanceLedger;
use App\Services\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function stay(string $status, int $rooms, int $roomTotal, Carbon $in, Carbon $out): Reservation
    {
        return Reservation::create([
            'reference' => 'HRK-'.strtoupper(Str::random(6)),
            'hotel_id' => 1,
            'guest_id' => Guest::create(['first_name' => 'B', 'last_name' => 'I'.random_int(100, 999), 'email' => 'bi'.random_int(1000, 9999).'@example.com'])->id,
            'room_category_id' => RoomCategory::first()->id,
            'status' => $status,
            'check_in' => $in->toDateString(),
            'check_out' => $out->toDateString(),
            'adults' => 2, 'rooms_count' => $rooms,
            'room_total' => $roomTotal, 'total' => $roomTotal,
        ]);
    }

    public function test_occupancy_reflects_added_stay(): void
    {
        $day = Carbon::today();
        $before = Analytics::occupancy($day, $day);

        // 2 chambres, 2 nuits à 50 000 → room_total 200 000, ADR nightly 50 000.
        $this->stay('checked_in', 2, 200000, $day->copy()->subDay(), $day->copy()->addDay());

        $after = Analytics::occupancy($day, $day);

        $this->assertSame($before['rooms_sold'] + 2, $after['rooms_sold']);
        $this->assertGreaterThan(0, $after['adr']);
        $this->assertGreaterThanOrEqual($after['revpar'], $after['adr']);
    }

    public function test_snapshot_writes_all_configured_metrics(): void
    {
        $date = Carbon::today()->subDay();
        $count = Analytics::snapshot($date);

        $this->assertSame(count(config('bi.snapshot_metrics')), $count);
        foreach (config('bi.snapshot_metrics') as $key) {
            $this->assertTrue(
                DailyMetric::where('key', $key)->whereDate('metric_date', $date->toDateString())->exists(),
                "métrique {$key} manquante",
            );
        }

        $series = Analytics::series('occupancy', $date, $date);
        $this->assertArrayHasKey($date->toDateString(), $series);
    }

    public function test_reservations_report_lists_the_period_and_exports_csv(): void
    {
        $res = $this->stay('confirmed', 1, 120000, Carbon::today()->addWeek(), Carbon::today()->addWeek()->addDays(2));

        $report = ReportRegistry::run('reservations', Carbon::today()->subDay(), Carbon::today()->addDay());

        $this->assertContains('Référence', $report['columns']);
        $this->assertTrue(collect($report['rows'])->pluck('Référence')->contains($res->reference));

        $csv = ReportRegistry::toCsv($report);
        $this->assertStringStartsWith('Référence,Client', $csv);
        $this->assertStringContainsString($res->reference, $csv);
    }

    public function test_revenue_daily_report_sums_income(): void
    {
        $account = FinanceAccount::where('type', 'cash')->first();
        FinanceLedger::record([
            'direction' => 'income', 'category' => 'divers_produits', 'method' => 'especes',
            'amount' => 44000, 'label' => 'Test BI', 'finance_account_id' => $account->id,
            'operation_date' => Carbon::today(),
        ]);

        $report = ReportRegistry::run('revenue_daily', Carbon::today(), Carbon::today());
        $todayRow = collect($report['rows'])->firstWhere('Date', Carbon::today()->format('Y-m-d'));

        $this->assertNotNull($todayRow);
        $this->assertGreaterThanOrEqual(44000, $todayRow['Total recettes']);
    }

    public function test_snapshot_command_runs(): void
    {
        $date = Carbon::today()->subDays(2)->toDateString();
        $this->artisan('bi:snapshot', ['--date' => $date])->assertSuccessful();

        $this->assertTrue(
            DailyMetric::where('key', 'total_revenue')->whereDate('metric_date', $date)->exists(),
        );
    }

    public function test_run_schedules_command_creates_a_run_and_emails_the_csv(): void
    {
        Mail::fake();

        // Le seeder crée une planification hebdomadaire (revenue_daily) : due un lundi.
        $monday = Carbon::today()->next(Carbon::MONDAY);

        $this->artisan('bi:run-schedules', ['--date' => $monday->toDateString()])->assertSuccessful();

        $this->assertDatabaseHas('report_runs', ['report_key' => 'revenue_daily']);

        // Le CSV part aux destinataires configurés sur la planification.
        Mail::assertQueued(
            ScheduledReport::class,
            fn ($m) => $m->hasTo('direction@residence-khadija.sn')
                && str_contains($m->filename, 'revenue-daily'),
        );
    }

    public function test_rbac_bi_scope(): void
    {
        $direction = User::where('role', 'direction')->firstOrFail();
        $finance = User::where('role', 'finance')->firstOrFail();
        $housekeeping = User::where('role', 'housekeeping')->firstOrFail();

        $this->actingAs($direction)->get(route('admin.bi.dashboard'))->assertOk();
        $this->actingAs($finance)->get(route('admin.bi.reports'))->assertOk();
        $this->actingAs($housekeeping)->get(route('admin.bi.dashboard'))->assertForbidden();
    }

    public function test_export_route_streams_csv(): void
    {
        $direction = User::where('role', 'direction')->firstOrFail();

        $res = $this->actingAs($direction)->get(route('admin.bi.export', [
            'key' => 'occupancy_daily', 'period' => '7d',
        ]));

        $res->assertOk();
        $this->assertStringContainsString('text/csv', $res->headers->get('content-type'));
    }
}
