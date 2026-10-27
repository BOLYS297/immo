<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\FrontRevalidator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/** Avis client du mur /recommandations du front. À publier uniquement avec l'accord écrit du client. */
class Testimonial extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'client_name',
        'role',
        'company',
        'pole',
        'photo_id',
        'content',
        'metric',
        'rating',
        'is_published',
        'sort_order',
    ];

    /** content = citation, metric = résultat mesuré (facultatif). */
    public array $translatable = ['content', 'metric'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => FrontRevalidator::tags('testimonials'));
        static::deleted(fn () => FrontRevalidator::tags('testimonials'));
    }

    /** Photo de la personne (médiathèque). */
    public function photo()
    {
        return $this->belongsTo(Media::class, 'photo_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }
}
