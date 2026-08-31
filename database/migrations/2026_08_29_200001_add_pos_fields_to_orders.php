<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caisse restaurant (POS) — champs de vente comptoir sur `orders`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 12)->default('qr')->after('reference');   // qr | pos
            $table->string('sale_type', 20)->nullable()->after('type');        // restaurant | bar | pool | room_service
            $table->string('table_label')->nullable()->after('guest_name');
            $table->unsignedInteger('discount')->default(0)->after('service_charge');
            $table->unsignedInteger('amount_tendered')->nullable()->after('total');
            $table->foreignId('cashier_id')->nullable()->after('reservation_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_id');
            $table->dropColumn(['source', 'sale_type', 'table_label', 'discount', 'amount_tendered']);
        });
    }
};
