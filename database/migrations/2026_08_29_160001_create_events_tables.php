<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 11 — Commercial & Événements (MICE) : pipeline, devis, événements,
 * réservation de salles (anti-double réservation), feuille de fonction (BEO).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('area')->nullable();          // m²
            $table->json('layouts')->nullable();                  // {"Théâtre":200,"Classe":110,...}
            $table->json('features')->nullable();
            $table->unsignedInteger('half_day_price')->default(0);
            $table->unsignedInteger('full_day_price')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('company')->nullable();
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 20)->default('seminaire');
            $table->date('expected_start')->nullable();
            $table->date('expected_end')->nullable();
            $table->unsignedInteger('pax')->nullable();
            $table->string('status', 15)->default('nouveau');     // nouveau, qualifie, devis, negociation, gagne, perdu
            $table->unsignedInteger('estimated_value')->default(0);
            $table->string('source', 20)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('event_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 12);
            $table->string('subject');
            $table->text('body')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->boolean('done')->default(false);
            $table->dateTime('occurred_at');
            $table->timestamps();
        });

        Schema::create('event_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('title');
            $table->unsignedInteger('pax')->default(0);
            $table->string('currency', 3)->default('XOF');
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.18);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->decimal('deposit_rate', 5, 4)->default(0.30);
            $table->unsignedInteger('deposit_amount')->default(0);
            $table->date('valid_until')->nullable();
            $table->string('status', 12)->default('draft');       // draft, sent, accepted, declined, expired
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_quote_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('label');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('name');
            $table->string('event_type', 20)->default('seminaire');
            $table->foreignId('event_lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name');
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('pax')->default(0);
            $table->string('layout', 20)->nullable();
            $table->string('status', 12)->default('option');      // option, confirme, realise, annule
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->date('option_expires_on')->nullable();
            $table->unsignedInteger('rooms_to_block')->default(0);
            $table->boolean('deposit_invoiced')->default(false);
            $table->boolean('deposit_paid')->default(false);
            $table->boolean('settled')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_space_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_space_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('layout', 20)->nullable();
            $table->string('setup_notes')->nullable();
            $table->timestamps();
            $table->index(['event_space_id', 'starts_at', 'ends_at']);
        });

        Schema::create('event_agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_min')->nullable();
            $table->string('area')->nullable();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('responsible')->nullable();
            $table->string('category', 15)->default('autre');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_agenda_items');
        Schema::dropIfExists('event_space_bookings');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_quote_items');
        Schema::dropIfExists('event_quotes');
        Schema::dropIfExists('event_lead_activities');
        Schema::dropIfExists('event_leads');
        Schema::dropIfExists('event_spaces');
    }
};
