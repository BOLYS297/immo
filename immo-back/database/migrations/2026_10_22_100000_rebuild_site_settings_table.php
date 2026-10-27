<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réglages du site : valeur JSON (chaîne, ou traductions { fr, en }) pour les clés déclarées dans config/cms.php.
 * L'ancienne table (clés plates de l'ancien site Blade) est conservée sous legacy_site_settings, sans être lue :
 * ses coordonnées différaient de celles affichées par le front, à reprendre à la main dans l'admin si besoin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('site_settings', 'legacy_site_settings');

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::rename('legacy_site_settings', 'site_settings');
    }
};
