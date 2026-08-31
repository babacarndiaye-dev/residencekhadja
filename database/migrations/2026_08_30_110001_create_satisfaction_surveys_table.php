<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Satisfaction / e-réputation (§ audit #10).
 * Une ligne = une invitation d'enquête liée à un séjour ; les réponses la
 * complètent (notes /5 par critère + NPS 0-10 + commentaire + accord de publication).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 48)->unique();
            $table->string('channel', 20)->default('post_stay');   // post_stay, manual, qr

            // Cycle de vie de l'invitation.
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('expires_on')->nullable();

            // Réponse (remplie à la complétion).
            $table->unsignedTinyInteger('rating_overall')->nullable();   // 1-5
            $table->unsignedTinyInteger('nps_score')->nullable();        // 0-10
            $table->json('category_ratings')->nullable();                // {"room":5,"staff":4,...}
            $table->text('comment')->nullable();
            $table->boolean('consent_publish')->default(false);
            $table->string('author_label', 120)->nullable();             // « Awa D., Dakar »

            // Traitement interne / e-réputation.
            $table->string('status', 12)->default('pending');           // pending, received, triaged, expired
            $table->text('staff_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->string('source_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index(['hotel_id', 'is_published']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_surveys');
    }
};
