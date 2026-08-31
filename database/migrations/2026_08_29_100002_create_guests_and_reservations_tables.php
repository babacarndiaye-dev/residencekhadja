<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('civility')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('company')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_category_id')->constrained();
            $table->foreignId('rate_plan_id')->nullable()->constrained();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete(); // affectée au check-in

            // pending, confirmed, checked_in, checked_out, cancelled, no_show
            $table->string('status')->default('pending')->index();
            $table->string('channel')->default('direct');

            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('rooms_count')->default(1);

            $table->string('currency', 3)->default('XOF');
            $table->unsignedInteger('room_total')->default(0);
            $table->unsignedInteger('extras_total')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('tourist_tax')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('deposit')->default(0);
            $table->string('promo_code')->nullable();

            $table->json('extras')->nullable();
            $table->json('special_requests')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('arrival_time', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('source_ip', 45)->nullable();

            $table->string('invoice_number')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'check_in', 'check_out']);
            $table->index(['room_category_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('amount'); // négatif = remboursement
            $table->string('method')->default('especes'); // especes, carte, virement, mobile
            $table->string('type')->default('deposit');    // deposit, balance, refund
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guests');
    }
};
