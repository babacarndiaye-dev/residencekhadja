<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // climatisation, ascenseur, groupe_electrogene, plomberie, electricite, piscine, cuisine, fitness, autre
            $table->string('category')->default('autre');
            $table->string('location')->nullable();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial')->nullable();
            $table->date('commissioned_on')->nullable();
            $table->string('status')->default('operational'); // operational, degraded, out_of_service
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('equipment_category')->nullable();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('interval_days');
            $table->json('checklist')->nullable();
            $table->string('priority')->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_run_on')->nullable();
            $table->date('next_due_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('maintenance_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('corrective');   // corrective, preventive
            $table->string('priority')->default('normal');    // low, normal, high, critical
            // open, assigned, in_progress, on_hold, resolved, closed
            $table->string('status')->default('open')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('resolution')->nullable();
            $table->unsignedInteger('labor_cost')->default(0);
            $table->unsignedInteger('parts_cost')->default(0);
            $table->date('due_on')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status', 'priority']);
        });

        Schema::table('housekeeping_incidents', function (Blueprint $table) {
            $table->foreign('maintenance_ticket_id')->references('id')->on('maintenance_tickets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_incidents', function (Blueprint $table) {
            $table->dropForeign(['maintenance_ticket_id']);
        });
        Schema::dropIfExists('maintenance_tickets');
        Schema::dropIfExists('maintenance_plans');
        Schema::dropIfExists('equipment');
    }
};
