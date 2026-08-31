<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session de caisse POS : on réutilise `cash_sessions` (module finance) en y
 * ajoutant le détail des coupures, et on relie chaque commande à sa session +
 * son serveur pour un Z-report exact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->json('opening_denominations')->nullable()->after('opening_float');
            $table->json('closing_denominations')->nullable()->after('counted_amount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('server_id')->nullable()->after('cashier_id')->constrained('users')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
            $table->timestamp('refunded_at')->nullable()->after('completed_at');
            $table->string('refund_reason')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('server_id');
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropColumn(['refunded_at', 'refund_reason']);
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn(['opening_denominations', 'closing_denominations']);
        });
    }
};
