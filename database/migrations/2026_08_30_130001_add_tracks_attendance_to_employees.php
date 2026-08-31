<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Soumis au pointage entrée / sortie à la borne (§44).
            // Les cadres au forfait, stagiaires ou prestataires peuvent en être dispensés.
            $table->boolean('tracks_attendance')->default(true)->after('pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('tracks_attendance');
        });
    }
};
