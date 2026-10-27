<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contenus structurés (phase 8) : services, réalisations et FAQ deviennent bilingues (FR/EN, spatie/translatable).
 * Les textes existants deviennent la version française. Les lignes existantes viennent toutes de l'ancien seeder
 * (prix en euros, clients et résultats inventés, réponses non validées) : elles passent hors ligne, sans être supprimées.
 * Les trois domaines affichés aujourd'hui sur /services deviennent les premiers vrais services (data/system-pages.json).
 */
return new class extends Migration
{
    private const TRANSLATABLE = [
        'services' => ['title', 'short_description', 'deliverables'],
        'projects' => ['title', 'short_description', 'problem_statement', 'solution_statement', 'verified_results'],
        'faqs' => ['question', 'answer', 'category'],
    ];

    public function up(): void
    {
        foreach (self::TRANSLATABLE as $table => $columns) {
            foreach ($columns as $column) {
                $this->toTranslations($table, $column);
            }
        }

        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('service_category_id')->nullable()->change();
            $table->longText('detailed_content')->nullable()->change();
            $table->json('cta_label')->nullable()->after('deliverables');
            $table->string('quote_domain', 10)->nullable()->after('cta_label'); // pôle présélectionné par le bouton : ia, web, infra
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_category_id')->nullable()->change();
            $table->foreignId('image_id')->nullable()->after('main_image')->constrained('media')->nullOnDelete();
        });

        // Données de démonstration de l'ancien seeder : hors ligne.
        foreach (array_keys(self::TRANSLATABLE) as $table) {
            DB::table($table)->update(['is_published' => false]);
        }

        $this->seedRealServices();
    }

    public function down(): void
    {
        DB::table('services')->whereNotNull('quote_domain')->delete();
        Schema::table('projects', fn (Blueprint $table) => $table->dropConstrainedForeignId('image_id'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn(['cta_label', 'quote_domain']));
        // Les colonnes restent au format JSON (traductions) : retour arrière des données non prévu.
    }

    /** Colonne texte (ou liste JSON) → traductions {"fr": valeur}. */
    private function toTranslations(string $table, string $column): void
    {
        $tmp = "{$column}_tr";
        Schema::table($table, fn (Blueprint $t) => $t->json($tmp)->nullable()->after($column));
        foreach (DB::table($table)->get(['id', $column]) as $row) {
            $value = $row->$column;
            if ($value === null || $value === '') {
                continue;
            }
            $decoded = json_decode($value, true);
            DB::table($table)->where('id', $row->id)->update([$tmp => json_encode(['fr' => is_array($decoded) ? $decoded : $value])]);
        }
        Schema::table($table, fn (Blueprint $t) => $t->dropColumn($column));
        Schema::table($table, fn (Blueprint $t) => $t->renameColumn($tmp, $column));
    }

    /** Les trois domaines de la page /services, textes identiques au site (FR/EN). */
    private function seedRealServices(): void
    {
        $path = database_path('seeders/data/system-pages.json');
        if (! is_file($path)) {
            return;
        }
        $section = collect(json_decode(file_get_contents($path), true)['services']['sections'] ?? [])->firstWhere('type', 'service_domains');
        if (! $section) {
            return;
        }
        $fr = $section['content']['fr']['domains'] ?? [];
        $en = $section['content']['en']['domains'] ?? [];
        $keys = ['ia', 'web', 'infra'];
        $icons = ['workflow', 'app-window', 'shield-check'];
        $lines = fn (?string $text) => array_values(array_filter(array_map('trim', explode("\n", (string) $text)), 'strlen'));

        foreach ($fr as $i => $domain) {
            $other = $en[$i] ?? [];
            DB::table('services')->insert([
                'title' => json_encode(['fr' => $domain['title'], 'en' => $other['title'] ?? null]),
                'slug' => $keys[$i] ?? 'service-'.($i + 1),
                'short_description' => json_encode(['fr' => $domain['benefit'], 'en' => $other['benefit'] ?? null]),
                'deliverables' => json_encode(['fr' => $lines($domain['deliverables'] ?? ''), 'en' => $lines($other['deliverables'] ?? '')]),
                'cta_label' => json_encode(['fr' => $domain['cta'], 'en' => $other['cta'] ?? null]),
                'quote_domain' => $keys[$i] ?? null,
                'icon' => $icons[$i] ?? 'workflow',
                'is_published' => true,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
