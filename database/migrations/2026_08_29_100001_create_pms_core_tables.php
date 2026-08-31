<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->nullable();
            $table->string('country')->default('Sénégal');
            $table->string('timezone')->default('Africa/Dakar');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('key');                 // flexible, non_remboursable
            $table->string('name');
            $table->decimal('multiplier', 5, 3)->default(1);
            $table->string('note')->nullable();
            $table->boolean('is_refundable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'key']);
        });

        Schema::create('room_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('category')->default('chambre'); // chambre | suite
            $table->unsignedInteger('price');               // "à partir de", par nuit, FCFA
            $table->unsignedSmallInteger('size')->default(0);
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->string('bed')->nullable();
            $table->string('view')->nullable();
            $table->string('short')->nullable();
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_category_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            // libre, occupee, sale, en_nettoyage, propre, controle, bloquee, hors_service
            $table->string('status')->default('propre');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hotel_id', 'number']);
            $table->index(['hotel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_categories');
        Schema::dropIfExists('rate_plans');
        Schema::dropIfExists('hotels');
    }
};
