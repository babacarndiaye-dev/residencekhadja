<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomenclature (recette) d'un article de carte : ses ingrédients de stock et la
 * quantité consommée par unité vendue. Sert au décrément automatique du stock à
 * la vente et au calcul du coût matière / de la marge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['menu_item_id', 'stock_item_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_applied_at')->nullable()->after('invoiced_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_applied_at');
        });

        Schema::dropIfExists('menu_item_recipes');
    }
};
