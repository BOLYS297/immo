<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Photo de la personne qui recommande (médiathèque). La colonne « avatar » de l'ancien site n'est plus utilisée. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->foreignId('photo_id')->nullable()->after('pole')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('photo_id');
        });
    }
};
