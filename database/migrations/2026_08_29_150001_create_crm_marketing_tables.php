<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 — CRM 360°, Fidélité & Marketing (§18–20, §52–57).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('phone');
            $table->string('locale', 5)->default('fr')->after('country');
            $table->boolean('marketing_opt_in')->default(false)->after('locale');
            $table->timestamp('consent_updated_at')->nullable()->after('marketing_opt_in');
            $table->string('acquisition_source')->nullable()->after('consent_updated_at');
            $table->json('tags')->nullable()->after('acquisition_source');
        });

        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedInteger('min_points')->default(0);
            $table->decimal('earn_rate', 5, 2)->default(1);   // points par tranche de 1 000 FCFA
            $table->json('perks')->nullable();
            $table->string('color', 9)->default('#b3893a');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['hotel_id', 'code']);
        });

        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->nullable()->constrained('loyalty_tiers')->nullOnDelete();
            $table->string('member_no', 20)->unique();
            $table->integer('points_balance')->default(0);
            $table->unsignedInteger('lifetime_points')->default(0);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->unique('guest_id');
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12);                 // earn, redeem, adjust, expire
            $table->integer('points');                  // signé
            $table->integer('balance_after');
            $table->string('reason');
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('guest_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);                 // note, call, email, sms, complaint, compliment
            $table->string('subject');
            $table->text('body')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('marketing_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('definition');                 // liste de règles
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('type', 10);                 // percent, amount
            $table->unsignedInteger('value');
            $table->string('label');
            $table->boolean('active')->default(true);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redeemed_count')->default(0);
            $table->timestamps();
            $table->unique(['hotel_id', 'code']);
        });

        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('channel', 10);              // email, sms
            $table->foreignId('segment_id')->nullable()->constrained('marketing_segments')->nullOnDelete();
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 12)->default('draft'); // draft, scheduled, sent, cancelled
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('stats')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('address')->nullable();      // email ou téléphone au moment de l'envoi
            $table->string('status', 12)->default('queued'); // queued, sent, skipped, failed
            $table->string('reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('marketing_segments');
        Schema::dropIfExists('guest_interactions');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('loyalty_tiers');

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'birthdate', 'locale', 'marketing_opt_in',
                'consent_updated_at', 'acquisition_source', 'tags',
            ]);
        });
    }
};
