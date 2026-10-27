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
        Schema::table('quote_requests', function (Blueprint $table) {
            // Pôle choisi dans le formulaire de chiffrage du front (ia, web, infra) et langue du visiteur.
            $table->string('domain', 20)->nullable()->after('timeline');
            $table->string('locale', 5)->nullable()->after('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropColumn(['domain', 'locale']);
        });
    }
};
