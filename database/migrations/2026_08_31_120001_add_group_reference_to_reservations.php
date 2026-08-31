<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réservation multi-chambres : une ligne `reservations` par chambre, toutes
 * reliées par la même `group_reference` (HRK-XXXXXX). La `reference` de chaque
 * chambre devient HRK-XXXXXX-1, -2, …
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('group_reference', 20)->nullable()->after('reference')->index();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('group_reference');
        });
    }
};
