<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Icône d'un service : image de la médiathèque (facultative, prioritaire sur le nom d'icône).
 * Pôle d'une demande de devis ou d'un rendez-vous : identifiant d'un service (jusqu'à 80 caractères).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('icon_media_id')->nullable()->after('icon')->constrained('media')->nullOnDelete();
        });
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('domain', 80)->nullable()->change();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('need', 80)->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('icon_media_id');
        });
    }
};
