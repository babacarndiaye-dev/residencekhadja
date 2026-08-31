<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue de services demandables depuis l'app invité (GEMS §4).
 * Un service payant crée immédiatement une ligne de folio (`reservation_charges`)
 * rattachée à la demande — `charge_id` permet la contre-passation si la demande
 * est annulée tant qu'elle est « Nouvelle ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_requests', function (Blueprint $table) {
            $table->string('service_slug', 40)->nullable()->after('type');
            $table->unsignedSmallInteger('quantity')->default(1)->after('service_slug');
            $table->unsignedInteger('price')->nullable()->after('quantity'); // PU FCFA (snapshot)
            $table->foreignId('charge_id')->nullable()->after('price')
                ->constrained('reservation_charges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('charge_id');
            $table->dropColumn(['service_slug', 'quantity', 'price']);
        });
    }
};
