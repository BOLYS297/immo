<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('prospects')->onDelete('cascade');
            $table->string('reference')->unique()->index();
            $table->string('budget_range')->nullable();
            $table->string('timeline')->nullable();
            $table->longText('description');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('status')->default('nouvelle'); // nouvelle, en_cours, devis_envoye, acceptee, refusee, cloturee
            $table->text('internal_notes')->nullable();
            $table->boolean('privacy_consent')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
