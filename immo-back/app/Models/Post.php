<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/** Article « Tech Insights » du front, en français et en anglais. */
class Post extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'summary',
        'content',
        'tags',
        'reading_minutes',
        'cover_image',
        'meta_title',
        'meta_description',
        'published_at',
        'is_published',
    ];

    public array $translatable = ['title', 'summary', 'content', 'tags', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'reading_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('posts'));
        static::deleted(fn () => FrontRevalidator::tags('posts'));
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Version d'une langue, complète ou absente : un article sans titre ou sans texte dans cette langue
     * est servi entièrement dans la langue par défaut (pas de mélange de langues dans un article).
     */
    public function localeFor(string $locale): string
    {
        $complete = fn (string $l) => filled($this->getTranslation('title', $l, false)) && filled($this->getTranslation('content', $l, false));

        return $complete($locale) ? $locale : config('cms.default_locale');
    }

    /** Contrat de l'API (immo-front/src/server/cms-api.ts, type Post) : liste, article et aperçu. */
    public function toApi(string $locale, bool $withBody = false): array
    {
        $l = $this->localeFor($locale);
        $text = fn (string $key) => $this->getTranslation($key, $l, false) ?: null;

        return [
            'slug' => $this->slug,
            'locale' => $l,
            'date' => $this->published_at->toDateString(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'readingMinutes' => $this->reading_minutes,
            'title' => $text('title'),
            'excerpt' => $text('summary') ?? '',
            'tags' => array_values($this->getTranslation('tags', $l, false) ?: []),
            'metaTitle' => $text('meta_title'),
            'metaDescription' => $text('meta_description'),
        ] + ($withBody ? ['body' => $text('content') ?? ''] : []);
    }
}
