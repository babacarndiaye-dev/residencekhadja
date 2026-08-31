<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zone de salle (« Salle », « Terrasse », « Rooftop »…) pour regrouper les
 * tables dans le plan de salle du POS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_locations', function (Blueprint $table) {
            $table->string('zone')->nullable()->after('label');
            $table->index(['venue_id', 'zone']);
        });
    }

    public function down(): void
    {
        Schema::table('qr_locations', function (Blueprint $table) {
            $table->dropIndex(['venue_id', 'zone']);
            $table->dropColumn('zone');
        });
    }
};
