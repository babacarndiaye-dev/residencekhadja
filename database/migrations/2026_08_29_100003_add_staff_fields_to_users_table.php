<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin, direction, reception, housekeeping
            $table->string('role')->default('reception')->after('email');
            $table->foreignId('hotel_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('job_title')->nullable()->after('hotel_id');
            $table->boolean('is_active')->default(true)->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hotel_id');
            $table->dropColumn(['role', 'job_title', 'is_active']);
        });
    }
};
