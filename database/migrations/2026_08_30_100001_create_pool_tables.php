<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Piscine : parc de transats / cabanas + réservations par créneau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);                 // transat | daybed | cabana
            $table->string('label', 60);
            $table->unsignedTinyInteger('capacity')->default(1);
            $table->unsignedInteger('half_day_price')->default(0);
            $table->unsignedInteger('full_day_price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'label']);
        });

        Schema::create('pool_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pool_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete(); // séjour lié
            $table->string('guest_name', 120);
            $table->string('guest_phone', 40)->nullable();
            $table->date('date');
            $table->string('slot', 12);                 // morning | afternoon | full_day
            $table->unsignedTinyInteger('guests')->default(1);
            $table->unsignedInteger('price')->default(0);
            $table->string('status', 12)->default('booked');
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['pool_asset_id', 'date']);
            $table->index(['hotel_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_reservations');
        Schema::dropIfExists('pool_assets');
    }
};
