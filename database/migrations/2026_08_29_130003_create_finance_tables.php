<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');     // cash | bank | mobile
            $table->integer('opening_balance')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finance_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');    // open | closed
            $table->integer('opening_float');             // fond de caisse
            $table->integer('counted_amount')->nullable();
            $table->integer('expected_amount')->nullable();
            $table->integer('variance')->nullable();      // counted - expected
            $table->text('note')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finance_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');                  // income | expense
            $table->string('category');
            $table->string('method')->default('especes');
            $table->integer('amount');                    // positif
            $table->string('label');
            $table->date('operation_date');
            $table->nullableMorphs('source');            // Payment, Order, SupplierInvoice, manuel…
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'direction', 'operation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('cash_sessions');
        Schema::dropIfExists('finance_accounts');
    }
};
