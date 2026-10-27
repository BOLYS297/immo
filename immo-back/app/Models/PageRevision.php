<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Version publiée d'une page : instantané de son titre, de son SEO et de ses sections au moment de la publication. */
class PageRevision extends Model
{
    public const UPDATED_AT = null;

    /** Valeurs des champs ajoutés après la création de la révision (SEO) : une ancienne version reste comparable. */
    public const DEFAULTS = ['og_image_id' => null, 'noindex' => false];

    protected $fillable = ['page_id', 'user_id', 'snapshot'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Instantané complété des champs absents des anciennes révisions. */
    public function fullSnapshot(): array
    {
        return $this->snapshot + self::DEFAULTS;
    }

    /** Sections de l'instantané, reconstruites en mémoire (non enregistrées) pour l'API et l'aperçu. */
    public function sections()
    {
        return collect($this->snapshot['sections'] ?? [])->values()->map(function (array $s, int $i) {
            $section = new PageSection;
            // Identifiant stable dans une révision : sert d'ancre et de clé React côté front.
            $section->forceFill(['id' => $this->id * 1000 + $i, 'page_id' => $this->page_id, 'type' => $s['type'], 'settings' => $s['settings'] ?? null, 'is_visible' => $s['is_visible'] ?? true, 'sort_order' => $i]);
            $section->setTranslations('content', $s['content'] ?? []);

            return $section;
        });
    }

    /** Identifiants des médias utilisés par cette version. */
    public function imageIds(): array
    {
        $og = $this->fullSnapshot()['og_image_id'];

        return array_values(array_unique(array_merge($og ? [(string) $og] : [], ...$this->sections()->map(fn (PageSection $s) => $s->imageIds())->all())));
    }
}
