<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contrôle du petit-déjeuner : un passage par chambre et par jour (anti double
 * comptage). Si le PDJ n'est pas inclus dans le séjour, un `reservation_charge`
 * est créé et référencé ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakfast_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->unsignedTinyInteger('guests')->default(1);
            $table->boolean('included')->default(true);
            $table->foreignId('reservation_charge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reservation_id', 'service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakfast_checkins');
    }
};
