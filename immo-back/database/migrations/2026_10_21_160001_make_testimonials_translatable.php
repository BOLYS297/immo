<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avis clients bilingues, au format du mur /recommandations du front :
 * fonction et entreprise séparées, pôle, citation et résultat mesuré par langue.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->renameColumn('client_role_company', 'role');
            $table->longText('content')->change();
        });

        DB::table('testimonials')->update(['content' => DB::raw("JSON_OBJECT('fr', `content`)")]);

        // Les avis existants viennent du jeu de démonstration de l'ancien site (personnes et chiffres inventés).
        // Le site n'affiche qu'un avis donné par écrit par un vrai client : ils sont conservés mais retirés du site.
        DB::table('testimonials')->update(['is_published' => false]);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->json('content')->change(); // citation {"fr", "en"}
            $table->string('company')->nullable()->after('role');
            $table->string('pole', 40)->nullable()->after('company'); // ex. « IA & Data »
            $table->json('metric')->nullable()->after('content'); // résultat mesuré {"fr", "en"}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['company', 'pole', 'metric']);
            $table->longText('content')->change();
        });

        DB::table('testimonials')->update(['content' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(`content`, '$.fr'))")]);

        Schema::table('testimonials', function (Blueprint $table) {
            $table->text('content')->change();
            $table->renameColumn('role', 'client_role_company');
        });
    }
};
