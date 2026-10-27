<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Workflow éditorial : les sections d'une page sont un brouillon ; « Publier » enregistre un instantané
 * (page_revisions.snapshot) que l'API publique sert. Les pages déjà en ligne reçoivent une première révision
 * identique à leur contenu actuel : le site ne change pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot'); // {title, meta_title, meta_description, sections: [{type, content, settings, is_visible}]}
            $table->timestamp('created_at')->useCurrent();

            $table->index(['page_id', 'id']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('published_revision_id')->nullable()->after('is_published')->constrained('page_revisions')->nullOnDelete();
        });

        $json = fn ($value) => $value === null ? null : json_decode($value, true);
        foreach (DB::table('pages')->where('is_published', true)->get() as $page) {
            $sections = DB::table('page_sections')->where('page_id', $page->id)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn ($s) => ['type' => $s->type, 'content' => $json($s->content) ?? [], 'settings' => $json($s->settings), 'is_visible' => (bool) $s->is_visible])
                ->all();
            $id = DB::table('page_revisions')->insertGetId([
                'page_id' => $page->id,
                'snapshot' => json_encode(['title' => $json($page->title) ?? [], 'meta_title' => $json($page->meta_title) ?? [], 'meta_description' => $json($page->meta_description) ?? [], 'sections' => $sections]),
                'created_at' => now(),
            ]);
            DB::table('pages')->where('id', $page->id)->update(['published_revision_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_revision_id');
        });
        Schema::dropIfExists('page_revisions');
    }
};
