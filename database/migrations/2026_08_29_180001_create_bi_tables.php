<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 13 — Décisionnel : instantanés quotidiens de KPI + rapports planifiés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->date('metric_date');
            $table->string('key', 40);
            $table->decimal('value', 16, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'metric_date', 'key']);
            $table->index(['key', 'metric_date']);
        });

        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 40);
            $table->string('frequency', 10)->default('weekly'); // daily, weekly, monthly
            $table->json('recipients')->nullable();
            $table->unsignedSmallInteger('range_days')->default(7);
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_key', 40);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('row_count')->default(0);
            $table->json('payload')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('daily_metrics');
    }
};
