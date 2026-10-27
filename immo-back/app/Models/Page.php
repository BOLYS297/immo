<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\Activity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Translatable\HasTranslations;

/**
 * Page composée de sections. Workflow éditorial : les sections, le titre et le SEO enregistrés dans l'admin
 * forment le brouillon ; « Publier » en fait un instantané (PageRevision), seul servi par l'API publique.
 * L'adresse (slug) et la mise hors ligne (is_published) s'appliquent immédiatement.
 */
class Page extends Model
{
    use HasTranslations, RecordsActivity;

    /** Révisions conservées par page (la version en ligne l'est toujours). */
    public const KEEP_REVISIONS = 30;

    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'og_image_id',
        'noindex',
        'is_published',
    ];

    public array $translatable = ['title', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'noindex' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Le cache du front ne change qu'avec ce qui est public : adresse, mise en ligne, version publiée.
        // Ancien et nouveau slug (un renommage vide le cache des deux URLs) ; « pages » = plan du site.
        static::saved(function (Page $page) {
            if ($page->wasRecentlyCreated || $page->wasChanged(['slug', 'is_published', 'published_revision_id'])) {
                FrontRevalidator::tags('pages', ...array_map(fn ($slug) => "page:{$slug}", array_filter(array_unique([$page->getOriginal('slug'), $page->slug]))));
            }
        });
        static::deleted(fn (Page $page) => FrontRevalidator::tags('pages', "page:{$page->slug}"));
    }

    /** Page servie par une route dédiée du front et liée depuis les menus (config cms.system_pages). */
    public function isSystem(): bool
    {
        return in_array($this->getOriginal('slug') ?? $this->slug, config('cms.system_pages'), true);
    }

    public function sections()
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function ogImage()
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public function revisions()
    {
        return $this->hasMany(PageRevision::class)->latest('id');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(PageRevision::class, 'published_revision_id');
    }

    /** En ligne : publiée au moins une fois et non mise hors ligne. */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_revision_id');
    }

    /** Instantané du brouillon (même forme que PageRevision::snapshot). */
    public function draftSnapshot(): array
    {
        return [
            'title' => $this->getTranslations('title'),
            'meta_title' => $this->getTranslations('meta_title'),
            'meta_description' => $this->getTranslations('meta_description'),
            'og_image_id' => $this->og_image_id,
            'noindex' => (bool) $this->noindex,
            'sections' => $this->sections()->get()->map(fn (PageSection $s) => [
                'type' => $s->type,
                'content' => $s->getTranslations('content'),
                'settings' => $s->settings,
                'is_visible' => $s->is_visible,
            ])->all(),
        ];
    }

    /** Le brouillon diffère-t-il de la version en ligne ? (toujours vrai pour une page jamais publiée) */
    public function hasUnpublishedChanges(): bool
    {
        $published = $this->publishedRevision;

        return ! $published || static::canonical($this->draftSnapshot()) !== static::canonical($published->fullSnapshot());
    }

    /** Met le brouillon en ligne : nouvelle révision, puis élagage des plus anciennes. */
    public function publish(?User $by = null): PageRevision
    {
        Activity::record('published', $this, user: $by);

        return Activity::quietly(fn () => DB::transaction(function () use ($by) {
            $revision = $this->revisions()->create(['user_id' => $by?->id, 'snapshot' => $this->draftSnapshot()]);
            $this->forceFill(['published_revision_id' => $revision->id, 'is_published' => true])->save();

            $keep = $this->revisions()->limit(static::KEEP_REVISIONS)->pluck('id');
            $this->revisions()->whereNotIn('id', $keep)->delete();

            return $revision;
        }));
    }

    /** Remplace le brouillon par une révision (à publier ensuite) ; sert aussi à annuler les changements non publiés. */
    public function restore(PageRevision $revision): void
    {
        abort_unless($revision->page_id === $this->id, 404);

        Activity::record($revision->id === $this->published_revision_id ? 'discarded' : 'restored', $this, ['revision' => $revision->created_at?->toDateTimeString()]);
        Activity::quietly(fn () => DB::transaction(function () use ($revision) {
            $snapshot = $revision->fullSnapshot();
            $this->sections()->delete();
            foreach (array_values($snapshot['sections'] ?? []) as $order => $s) {
                $section = $this->sections()->make(['type' => $s['type'], 'settings' => $s['settings'] ?? null, 'is_visible' => $s['is_visible'] ?? true, 'sort_order' => $order]);
                $section->setTranslations('content', $s['content'] ?? []);
                $section->save();
            }
            foreach (['title', 'meta_title', 'meta_description'] as $key) {
                $this->setTranslations($key, $snapshot[$key] ?? []);
            }
            $this->forceFill(['og_image_id' => $snapshot['og_image_id'], 'noindex' => $snapshot['noindex']])->save();
        }));
    }

    /** Version en ligne, pour l'API publique : titre, SEO et sections visibles de la révision publiée. */
    public function publicVersion(): ?Page
    {
        return $this->publishedRevision ? $this->revisionVersion($this->publishedRevision) : null;
    }

    /** Page telle qu'enregistrée dans une révision (version en ligne, ou ancienne version pour l'aperçu). */
    public function revisionVersion(PageRevision $revision): Page
    {
        $copy = clone $this;
        $snapshot = $revision->fullSnapshot();
        foreach (['title', 'meta_title', 'meta_description'] as $key) {
            $copy->setTranslations($key, $snapshot[$key] ?? []);
        }
        $copy->forceFill(['og_image_id' => $snapshot['og_image_id'], 'noindex' => $snapshot['noindex']]);

        return $copy->setRelation('sections', $revision->sections()->filter(fn (PageSection $s) => $s->is_visible)->values());
    }

    /**
     * Brouillon pour l'aperçu : sections visibles enregistrées, avec éventuellement le contenu non encore
     * enregistré d'une section en cours d'édition ($overrides : [id => ['content' => …, 'settings' => …, 'is_visible' => …]]).
     */
    public function draftVersion(array $overrides = []): Page
    {
        $sections = $this->sections()->get()->map(function (PageSection $section) use ($overrides) {
            if ($override = $overrides[$section->id] ?? null) {
                $section->setTranslations('content', $override['content']);
                $section->settings = $override['settings'];
                $section->is_visible = $override['is_visible'];
            }

            return $section;
        });

        return (clone $this)->setRelation('sections', $sections->filter(fn (PageSection $s) => $s->is_visible)->values());
    }

    /** Forme comparable d'un instantané : clés triées (MySQL réordonne les clés JSON), langues vides retirées. */
    private static function canonical(array $snapshot): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (! is_array($value)) {
                return $value;
            }
            $value = array_map($normalize, $value);
            if (! array_is_list($value)) {
                ksort($value);
            }

            return $value;
        };

        return json_encode($normalize($snapshot));
    }

    /** Toutes les sections publiées (toutes pages) qui utilisent ce média : voir Media::usages(). */
    public static function publishedUsing(int $mediaId): Collection
    {
        return static::with('publishedRevision')->whereNotNull('published_revision_id')->get()
            ->filter(fn (Page $page) => in_array((string) $mediaId, $page->publishedRevision->imageIds(), true))
            ->values();
    }
}
