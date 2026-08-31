<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exercices comptables : période, statut (ouvert / clôturé), résultat figé.
 * Un exercice clôturé verrouille toute écriture datée dans sa période
 * (App\Services\Accounting::post).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('label', 20);            // ex. « 2026 »
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 10)->default('open'); // open | closed
            $table->integer('result_amount')->nullable();   // résultat net figé à la clôture
            $table->foreignId('closing_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'label']);
            $table->index(['hotel_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
