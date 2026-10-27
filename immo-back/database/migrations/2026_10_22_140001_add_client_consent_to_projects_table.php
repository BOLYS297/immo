<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Une réalisation nomme un client et ses résultats : sa mise en ligne exige l'accord écrit du client (date de l'accord). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('client_consent_at')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client_consent_at');
        });
    }
};
