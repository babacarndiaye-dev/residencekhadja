<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi room service (départ / livraison) + facture A4 numérotée par commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('out_for_delivery_at')->nullable()->after('served_at');
            $table->timestamp('delivered_at')->nullable()->after('out_for_delivery_at');
            $table->string('invoice_number')->nullable()->unique()->after('reference');
            $table->timestamp('invoiced_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['out_for_delivery_at', 'delivered_at', 'invoice_number', 'invoiced_at']);
        });
    }
};
