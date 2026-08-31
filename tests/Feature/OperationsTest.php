<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingIncident;
use App\Models\HousekeepingTask;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\User;
use App\Services\HousekeepingPlanner;
use App\Services\PreventiveMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function housekeeper(): User
    {
        return User::where('role', 'housekeeping')->firstOrFail();
    }

    private function technician(): User
    {
        return User::where('role', 'maintenance')->firstOrFail();
    }

    private function dirtyRoom(): Room
    {
        $room = Room::whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))->first();
        $room->update(['status' => 'sale']);

        return $room;
    }

    /* --------------------------- Housekeeping ------------------------- */

    public function test_planner_creates_departure_task_for_dirty_room(): void
    {
        $room = $this->dirtyRoom();

        $created = HousekeepingPlanner::generateForDate(Hotel::current(), Carbon::today());

        $this->assertGreaterThanOrEqual(1, $created);
        $this->assertDatabaseHas('housekeeping_tasks', [
            'room_id' => $room->id, 'type' => 'departure', 'status' => 'pending',
        ]);
    }

    public function test_checkout_creates_a_departure_task(): void
    {
        $category = RoomCategory::first();
        $room = Room::whereNotIn('id', Reservation::whereNotNull('room_id')->pluck('room_id'))->first();
        $reservation = Reservation::create([
            'reference' => 'HRK-HK0001', 'hotel_id' => $category->hotel_id,
            'guest_id' => Guest::create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'xy@example.com'])->id,
            'room_category_id' => $category->id, 'room_id' => $room->id, 'status' => 'checked_in',
            'check_in' => Carbon::yesterday()->toDateString(), 'check_out' => Carbon::today()->toDateString(),
            'adults' => 1, 'rooms_count' => 1, 'total' => 50000,
        ]);
        $room->update(['status' => 'occupee']);

        $this->actingAs(User::where('role', 'reception')->first())
            ->post(route('admin.reservations.check_out', $reservation), [])
            ->assertRedirect();

        $this->assertDatabaseHas('housekeeping_tasks', [
            'room_id' => $room->id, 'type' => 'departure',
        ]);
    }

    public function test_mobile_screen_lists_todays_tasks_and_filters_to_mine(): void
    {
        $room = $this->dirtyRoom();
        HousekeepingPlanner::generateForDate(Hotel::current(), Carbon::today());
        $task = HousekeepingTask::where('room_id', $room->id)->firstOrFail();

        $agent = $this->housekeeper();

        // Vue « tout l'étage » : la tâche apparaît.
        $this->actingAs($agent)->get(route('admin.housekeeping.mobile', ['mine' => 0]))
            ->assertOk()
            ->assertSee('Ch. '.$room->number);

        // Vue « mes chambres » : rien tant que non affectée…
        $this->actingAs($agent)->get(route('admin.housekeeping.mobile', ['mine' => 1]))
            ->assertOk()
            ->assertDontSee('Ch. '.$room->number);

        // …puis on l'affecte et elle réapparaît, avançable au doigt.
        $task->update(['assigned_to' => $agent->id]);
        $this->actingAs($agent)->get(route('admin.housekeeping.mobile', ['mine' => 1]))
            ->assertOk()->assertSee('Ch. '.$room->number);

        $this->actingAs($agent)->post(route('admin.housekeeping.status', $task), ['status' => 'done'])
            ->assertRedirect();
        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_qc_pass_marks_room_clean_and_fail_sends_it_back(): void
    {
        $room = $this->dirtyRoom();
        HousekeepingPlanner::generateForDate(Hotel::current(), Carbon::today());
        $task = HousekeepingTask::where('room_id', $room->id)->firstOrFail();

        // Ouvre la check-list.
        $this->actingAs($this->housekeeper())->get(route('admin.housekeeping.inspect', $task))->assertOk();
        $task->refresh()->load('checks');
        $checks = $task->checks;

        // Tout coché -> 100% -> validée, chambre propre.
        $payload = ['checks' => $checks->mapWithKeys(fn ($c) => [$c->id => ['passed' => '1']])->all()];
        $this->actingAs($this->housekeeper())
            ->post(route('admin.housekeeping.inspect.store', $task), $payload)
            ->assertRedirect();

        $task->refresh();
        $this->assertSame('inspected', $task->status);
        $this->assertSame(100, $task->qc_score);
        $this->assertSame('propre', $room->refresh()->status);

        // Réinspection avec échec -> repart en nettoyage.
        $fail = ['checks' => $checks->mapWithKeys(fn ($c) => [$c->id => []])->all()];
        $this->actingAs($this->housekeeper())->post(route('admin.housekeeping.inspect.store', $task), $fail)->assertRedirect();

        $this->assertSame('pending', $task->refresh()->status);
        $this->assertSame('sale', $room->refresh()->status);
    }

    public function test_technical_incident_opens_a_maintenance_ticket(): void
    {
        $room = Room::first();
        $before = MaintenanceTicket::count();

        $this->actingAs($this->housekeeper())->post(route('admin.housekeeping.incidents.store'), [
            'room_id' => $room->id,
            'category' => 'maintenance',
            'description' => 'Prise électrique qui ne tient plus.',
        ])->assertRedirect();

        $this->assertSame($before + 1, MaintenanceTicket::count());
        $this->assertDatabaseHas('housekeeping_incidents', ['room_id' => $room->id, 'category' => 'maintenance']);
        $this->assertNotNull(HousekeepingIncident::latest()->first()->maintenance_ticket_id);
    }

    /* --------------------------- Maintenance ------------------------- */

    public function test_preventive_run_generates_due_tickets_and_advances_the_plan(): void
    {
        $plan = MaintenancePlan::first();
        $plan->update(['next_due_on' => Carbon::yesterday()->toDateString(), 'last_run_on' => null]);
        // Ferme un éventuel ticket préventif encore ouvert issu du seed.
        MaintenanceTicket::where('maintenance_plan_id', $plan->id)->update(['status' => 'closed']);

        $created = PreventiveMaintenance::run();

        $this->assertGreaterThanOrEqual(1, $created);
        $plan->refresh();
        $this->assertTrue($plan->next_due_on->isFuture());
        $this->assertSame(today()->toDateString(), $plan->last_run_on->toDateString());
        $this->assertDatabaseHas('maintenance_tickets', [
            'maintenance_plan_id' => $plan->id, 'type' => 'preventive',
        ]);
    }

    public function test_preventive_run_is_idempotent_while_a_ticket_is_open(): void
    {
        $plan = MaintenancePlan::first();
        $plan->update(['next_due_on' => Carbon::yesterday()->toDateString()]);
        MaintenanceTicket::where('maintenance_plan_id', $plan->id)->update(['status' => 'open']);

        $this->assertSame(0, PreventiveMaintenance::run());
    }

    public function test_resolving_ticket_restores_equipment_status(): void
    {
        $equipment = Equipment::first();
        $equipment->update(['status' => 'out_of_service']);

        $ticket = MaintenanceTicket::create([
            'reference' => 'M-TST001', 'hotel_id' => $equipment->hotel_id,
            'equipment_id' => $equipment->id, 'type' => 'corrective', 'priority' => 'high',
            'status' => 'in_progress', 'title' => 'Test',
        ]);

        $this->actingAs($this->technician())->put(route('admin.maintenance.ticket.update', $ticket), [
            'title' => 'Test', 'priority' => 'high', 'status' => 'resolved',
            'equipment_id' => $equipment->id, 'labor_cost' => 5000, 'parts_cost' => 2000,
            'resolution' => 'Réparé.',
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertSame(7000, $ticket->totalCost());
        $this->assertNotNull($ticket->resolved_at);
        $this->assertSame('operational', $equipment->refresh()->status);
    }

    public function test_rbac_maintenance_role_scope(): void
    {
        $this->actingAs($this->technician())->get(route('admin.maintenance.tickets'))->assertOk();
        $this->actingAs($this->technician())->get(route('admin.maintenance.plans'))->assertOk();
        $this->actingAs($this->housekeeper())->get(route('admin.maintenance.tickets'))->assertForbidden();
        $this->actingAs($this->technician())->get(route('admin.housekeeping.index'))->assertForbidden();
    }

    public function test_console_command_runs(): void
    {
        $this->artisan('maintenance:run-plans')->assertSuccessful();
    }
}
