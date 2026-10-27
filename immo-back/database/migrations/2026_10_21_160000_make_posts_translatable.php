<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Articles bilingues (Tech Insights du front) : chaque texte devient {"fr": "...", "en": "..."}.
 * Les articles existants sont conservés en version française.
 * Le corps suit la syntaxe du front : « ## » titre, lignes « - » liste, paragraphes séparés par une ligne vide.
 */
return new class extends Migration
{
    private const TEXTS = ['title', 'summary', 'content', 'meta_title', 'meta_description'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. En texte long d'abord : la valeur encodée en JSON dépasse les 255 caractères d'un varchar.
        Schema::table('posts', function (Blueprint $table) {
            foreach (self::TEXTS as $column) {
                $table->longText($column)->nullable()->change();
            }
        });

        foreach (self::TEXTS as $column) {
            DB::table('posts')->whereNotNull($column)->update([$column => DB::raw("JSON_OBJECT('fr', `{$column}`)")]);
        }

        // Articles existants = jeu de démonstration de l'ancien site Blade, jamais relu pour le nouveau site :
        // conservés en brouillon, à republier depuis l'admin après relecture.
        DB::table('posts')->update(['is_published' => false]);

        Schema::table('posts', function (Blueprint $table) {
            $table->json('title')->change();
            $table->json('summary')->change();
            $table->json('content')->change();
            $table->json('meta_title')->nullable()->change();
            $table->json('meta_description')->nullable()->change();
            $table->json('tags')->nullable()->after('content'); // {"fr": ["IA", "n8n"], "en": [...]}
            $table->unsignedTinyInteger('reading_minutes')->default(5)->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['tags', 'reading_minutes']);
            foreach (self::TEXTS as $column) {
                $table->longText($column)->nullable()->change();
            }
        });

        foreach (self::TEXTS as $column) {
            DB::table('posts')->whereNotNull($column)->update([$column => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.fr'))")]);
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->string('title')->change();
            $table->text('summary')->change();
            $table->longText('content')->change();
            $table->string('meta_title')->nullable()->change();
            $table->string('meta_description')->nullable()->change();
        });
    }
};
