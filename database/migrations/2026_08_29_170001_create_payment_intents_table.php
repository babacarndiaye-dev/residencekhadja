<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12 — Paiement en ligne : intentions de paiement (§25).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 24)->unique();
            $table->string('purpose', 24);              // reservation_deposit, reservation_balance, order, event_deposit, event_balance
            $table->nullableMorphs('payable');
            $table->string('payer_name')->nullable();
            $table->string('payer_email')->nullable();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('provider', 20)->default('simulator');
            $table->string('provider_ref')->nullable()->index();
            $table->string('method', 20)->nullable();
            $table->string('status', 12)->default('pending'); // pending, processing, paid, failed, expired, refunded, cancelled
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purpose', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
