<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complétion RH : sortie de salarié, quotient familial (paie), jours fériés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Quotient familial (barème IRPP sénégalais).
            $table->string('marital_status', 12)->default('celibataire')->after('gender');
            $table->unsignedTinyInteger('dependents_count')->default(0)->after('marital_status');

            // Sortie de salarié.
            $table->date('termination_date')->nullable()->after('leave_balance_days');
            $table->string('termination_type', 30)->nullable()->after('termination_date');
            $table->date('notice_end_date')->nullable()->after('termination_type');
            $table->text('termination_notes')->nullable()->after('notice_end_date');
        });

        Schema::create('public_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['hotel_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'marital_status', 'dependents_count',
                'termination_date', 'termination_type', 'notice_end_date', 'termination_notes',
            ]);
        });
    }
};
