<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Service (domaine d'expertise) affiché par le bloc « Domaines d'expertise » réglé sur « Services de l'admin ».
 * Les colonnes detailed_content, needs_addressed, client_benefits, image et indicative_price viennent de l'ancien
 * site Blade : conservées en base, elles ne sont plus éditées ni envoyées au front.
 */
class Service extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'deliverables',
        'cta_label',
        'quote_domain',
        'icon',
        'is_published',
        'sort_order',
    ];

    public array $translatable = ['title', 'short_description', 'deliverables', 'cta_label'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('services'));
        static::deleted(fn () => FrontRevalidator::tags('services'));
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function quoteRequests()
    {
        return $this->belongsToMany(QuoteRequest::class, 'quote_request_service');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /** Contrat de l'API (immo-front/src/server/cms-api.ts, type ServiceView). Langue vide = français. */
    public function toApi(string $locale): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->getTranslation('title', $locale) ?: null,
            'description' => $this->getTranslation('short_description', $locale) ?: null,
            'deliverables' => array_values(array_filter((array) ($this->getTranslation('deliverables', $locale) ?: []), 'strlen')),
            'cta' => $this->getTranslation('cta_label', $locale) ?: null,
            'domain' => $this->quote_domain,
            'icon' => $this->icon,
        ];
    }
}
