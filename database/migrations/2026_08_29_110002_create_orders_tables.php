<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type')->default('dine_in');       // dine_in | room_service
            // new, preparing, ready, served, completed, cancelled
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('unpaid'); // unpaid | paid | charged_to_room
            $table->string('payment_method')->nullable();        // especes | carte | mobile | chambre

            $table->string('guest_name')->nullable();
            $table->string('session_token', 64)->nullable()->index();
            $table->string('idempotency_key', 64)->nullable()->unique();

            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('service_charge')->default(0);
            $table->unsignedInteger('tax')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->text('note')->nullable();

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                    // snapshot
            $table->unsignedInteger('unit_price');     // snapshot, options incluses
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->json('options')->nullable();       // [{group,name,price_delta}]
            $table->string('note')->nullable();
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            // assistance | water | cutlery | info | bill | other
            $table->string('type')->default('assistance');
            $table->string('note')->nullable();
            $table->string('status')->default('open')->index(); // open | acknowledged | done
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Folio : consommations imputées sur une réservation (§29 imputation chambre).
        Schema::create('reservation_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('source');          // Order, ...
            $table->string('label');
            $table->integer('amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_charges');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
