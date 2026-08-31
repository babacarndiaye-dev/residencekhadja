<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('kind');                            // earning | deduction
            $table->string('calc');                            // fixed | percent_base | rate_per_hour
            $table->boolean('is_taxable')->default(true);
            $table->boolean('applies_to_all')->default(false);
            $table->string('system_role')->nullable();         // base | overtime | absence | advance | adjustment
            $table->unsignedInteger('default_amount')->nullable();
            $table->decimal('default_rate', 6, 3)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['hotel_id', 'code']);
        });

        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount')->nullable();
            $table->decimal('rate', 6, 3)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'salary_component_id'], 'emp_salary_comp_unique');
        });

        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('repaid_amount')->default(0);
            $table->date('granted_on');
            $table->string('status')->default('outstanding');  // outstanding | repaid | cancelled
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);                       // YYYY-MM
            $table->string('label')->nullable();
            $table->string('status')->default('draft');        // draft | approved | paid
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('gross_total')->default(0);
            $table->unsignedInteger('deduction_total')->default(0);
            $table->unsignedInteger('net_total')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'period']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_contract_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('worked_days', 6, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('absence_days', 6, 2)->default(0);
            $table->unsignedInteger('gross')->default(0);
            $table->unsignedInteger('taxable_gross')->default(0);
            $table->unsignedInteger('total_deductions')->default(0);
            $table->integer('net')->default(0);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('label');
            $table->string('kind');                            // earning | deduction
            $table->unsignedInteger('base')->nullable();
            $table->decimal('rate', 8, 3)->nullable();
            $table->integer('amount');
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind');                            // earning | deduction
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('salary_advances');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
    }
};
