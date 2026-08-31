<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14 — Distribution & Channel Manager : calendrier ARI (disponibilité +
 * restrictions), canaux, mapping tarifaire, réservations entrantes, journal de sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('key', 30);
            $table->string('name');
            $table->string('type', 15)->default('ota');
            $table->string('connector', 20)->default('simulator');
            $table->decimal('commission_rate', 5, 4)->default(0.15);
            $table->string('currency', 3)->default('XOF');
            $table->json('credentials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'key']);
        });

        Schema::create('availability_calendar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('rooms_open')->nullable();   // null = capacité physique
            $table->unsignedSmallInteger('min_stay')->default(1);
            $table->unsignedSmallInteger('max_stay')->nullable();
            $table->boolean('cta')->default(false);                   // closed to arrival
            $table->boolean('ctd')->default(false);                   // closed to departure
            $table->boolean('stop_sell')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['room_category_id', 'date']);
        });

        Schema::create('channel_rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->cascadeOnDelete();
            $table->string('external_code')->nullable();
            $table->decimal('markup_rate', 5, 4)->default(0);         // 0 = parité
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel_id', 'rate_plan_id']);
        });

        Schema::create('channel_rate_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('price');
            $table->timestamps();

            $table->unique(['channel_id', 'room_category_id', 'date']);
        });

        Schema::create('channel_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_ref', 60);
            $table->string('status', 15)->default('new');             // new, imported, cancelled, failed
            $table->unsignedInteger('gross_amount')->default(0);
            $table->unsignedInteger('commission_amount')->default(0);
            $table->boolean('commission_posted')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'external_ref']);
        });

        Schema::create('channel_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);                             // push_availability, push_rates, pull_reservation
            $table->string('status', 12)->default('ok');              // ok, error
            $table->date('range_start')->nullable();
            $table->date('range_end')->nullable();
            $table->unsignedInteger('records')->default(0);
            $table->string('message')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_sync_logs');
        Schema::dropIfExists('channel_reservations');
        Schema::dropIfExists('channel_rate_overrides');
        Schema::dropIfExists('channel_rate_plans');
        Schema::dropIfExists('availability_calendar');
        Schema::dropIfExists('channels');
    }
};
