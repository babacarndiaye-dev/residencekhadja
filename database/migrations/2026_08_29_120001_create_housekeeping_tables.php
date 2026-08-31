<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->date('service_date');
            // departure, stayover, deep_clean, inspection, touch_up
            $table->string('type')->default('stayover');
            // pending, in_progress, done, blocked, inspected
            $table->string('status')->default('pending')->index();
            $table->string('priority')->default('normal');   // normal, high
            $table->string('consumables_note')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('qc_score')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'service_date', 'type']);
            $table->index(['hotel_id', 'service_date', 'status']);
        });

        Schema::create('housekeeping_task_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housekeeping_task_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('passed')->nullable();
            $table->string('comment')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('housekeeping_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('housekeeping_task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('maintenance_ticket_id')->nullable(); // FK ajoutée après création de la table
            // damage, missing_item, lost_found, cleanliness, maintenance, other
            $table->string('category')->default('other');
            $table->text('description');
            $table->string('status')->default('open')->index(); // open, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_incidents');
        Schema::dropIfExists('housekeeping_task_checks');
        Schema::dropIfExists('housekeeping_tasks');
    }
};
