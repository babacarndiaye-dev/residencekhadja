<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('type')->nullable();            // restaurant, bar, rooftop, pool...
            $table->string('hours')->nullable();
            $table->boolean('accepts_qr_orders')->default(true);
            $table->boolean('is_room_service')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });

        Schema::create('qr_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();             // ex: TBL-ROOF-12, ROOM-201
            $table->string('label');                       // "Table 12", "Chambre 201"
            $table->string('type')->default('table');      // table | room | spot
            $table->unsignedTinyInteger('seats')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('room_service')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });

        Schema::create('menu_category_venue', function (Blueprint $table) {
            $table->foreignId('menu_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->primary(['menu_category_id', 'venue_id']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('story')->nullable();             // storytelling (§25)
            $table->unsignedInteger('price');
            $table->string('image')->nullable();
            $table->json('allergens')->nullable();
            $table->json('tags')->nullable();              // végétarien, épicé, sans gluten...
            $table->boolean('is_available')->default(true);
            $table->boolean('is_signature')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });

        Schema::create('menu_option_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // "Cuisson", "Suppléments"
            $table->string('type')->default('single');     // single | multi
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('menu_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('price_delta')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_options');
        Schema::dropIfExists('menu_option_groups');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_category_venue');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('qr_locations');
        Schema::dropIfExists('venues');
    }
};
