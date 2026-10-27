<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/** Question fréquente, affichée par le bloc « FAQ (liste gérée) », filtrable par catégorie. */
class Faq extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'sort_order',
        'is_published',
    ];

    public array $translatable = ['question', 'answer', 'category'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('faqs'));
        static::deleted(fn () => FrontRevalidator::tags('faqs'));
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Contrat de l'API (immo-front/src/server/cms-api.ts, type FaqView). */
    public function toApi(string $locale): array
    {
        return [
            'id' => $this->id,
            'question' => $this->getTranslation('question', $locale) ?: null,
            'answer' => $this->getTranslation('answer', $locale) ?: null,
            'category' => $this->getTranslation('category', config('cms.default_locale')) ?: null,
        ];
    }
}
