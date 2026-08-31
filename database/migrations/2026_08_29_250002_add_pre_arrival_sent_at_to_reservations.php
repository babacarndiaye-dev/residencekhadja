<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marque l'envoi de l'e-mail de pré-arrivée (commande reservations:pre-arrival)
 * pour éviter les doublons.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('pre_arrival_sent_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('pre_arrival_sent_at');
        });
    }
};
