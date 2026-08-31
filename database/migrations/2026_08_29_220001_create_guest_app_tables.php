<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 15 — Application mobile invité (PWA) : jetons d'accès + demandes chambre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('label')->nullable();          // user-agent court
            $table->ipAddress('ip')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });

        Schema::create('guest_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('note', 400)->nullable();
            $table->string('routed_to', 15)->default('reception'); // reception | housekeeping | maintenance
            $table->string('status', 15)->default('open');          // open | acknowledged | done | cancelled
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'routed_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_requests');
        Schema::dropIfExists('guest_devices');
    }
};
