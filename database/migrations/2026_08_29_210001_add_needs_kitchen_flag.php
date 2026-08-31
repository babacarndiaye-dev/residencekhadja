<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * KDS — n'envoyer en cuisine que les articles à préparer (pas les boissons / bar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->boolean('needs_kitchen')->default(true)->after('room_service');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('needs_kitchen')->default(true)->after('menu_item_id');
        });

        // Les catégories de boissons / bar ne passent pas par la cuisine.
        DB::table('menu_categories')
            ->where(function ($q) {
                foreach (['boisson', 'cocktail', 'bar', 'bière', 'biere', 'vin', 'jus', 'soft', 'eau', 'café', 'cafe', 'thé', 'the '] as $kw) {
                    $q->orWhereRaw('LOWER(name) LIKE ?', ["%{$kw}%"]);
                }
                $q->orWhereIn('slug', ['boissons', 'cocktails', 'bar', 'vins', 'cafes', 'softs']);
            })
            ->update(['needs_kitchen' => false]);

        // Aligner l'historique des lignes de commande sur leur catégorie.
        DB::statement('
            UPDATE order_items SET needs_kitchen = 0
            WHERE menu_item_id IN (
                SELECT mi.id FROM menu_items mi
                JOIN menu_categories mc ON mc.id = mi.menu_category_id
                WHERE mc.needs_kitchen = 0
            )
        ');
    }

    public function down(): void
    {
        Schema::table('menu_categories', fn (Blueprint $t) => $t->dropColumn('needs_kitchen'));
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('needs_kitchen'));
    }
};
