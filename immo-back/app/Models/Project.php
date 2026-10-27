<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Réalisation (cas client), affichée par le bloc « Réalisations ».
 * Colonne main_image : ancien site Blade, remplacée par image_id (médiathèque).
 */
class Project extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'title',
        'slug',
        'client_name',
        'short_description',
        'problem_statement',
        'solution_statement',
        'technologies',
        'verified_results',
        'image_id',
        'demo_url',
        'completion_date',
        'is_published',
        'is_featured',
        'client_consent_at',
        'sort_order',
    ];

    public array $translatable = ['title', 'short_description', 'problem_statement', 'solution_statement', 'verified_results'];

    protected function casts(): array
    {
        return [
            'technologies' => 'array',
            'completion_date' => 'date',
            'client_consent_at' => 'date',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('projects'));
        static::deleted(fn () => FrontRevalidator::tags('projects'));
    }

    public function image()
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function category()
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    /** En ligne : publiée ET accord écrit du client enregistré. */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('client_consent_at');
    }

    public function scopeFeatured($query)
    {
        return $query->published()->where('is_featured', true);
    }

    /** Contrat de l'API (immo-front/src/server/cms-api.ts, type ProjectView). Langue vide = français. */
    public function toApi(string $locale): array
    {
        $text = fn (string $key) => $this->getTranslation($key, $locale) ?: null;

        return [
            'slug' => $this->slug,
            'title' => $text('title'),
            'client' => $this->client_name,
            'description' => $text('short_description'),
            'problem' => $text('problem_statement'),
            'solution' => $text('solution_statement'),
            'results' => $text('verified_results'),
            'technologies' => array_values((array) ($this->technologies ?? [])),
            'image' => $this->image?->toApi($locale),
            'url' => $this->demo_url,
            'date' => $this->completion_date?->toDateString(),
            'featured' => $this->is_featured,
        ];
    }
}
