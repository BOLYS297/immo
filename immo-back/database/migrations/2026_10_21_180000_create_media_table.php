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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 40); // disque au moment de l'envoi : un changement de MEDIA_DISK ne casse pas les anciens fichiers
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 60);
            $table->unsignedInteger('size'); // octets
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->json('alt')->nullable(); // texte alternatif {"fr", "en"}
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
