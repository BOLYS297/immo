<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Services\BlockSchema;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PageSection extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = [
        'page_id',
        'type',
        'content',
        'settings',
        'is_visible',
        'sort_order',
    ];

    /** content = {"fr": {champ: valeur, ...}, "en": {...}} */
    public array $translatable = ['content'];

    // Toucher la page déclenche son événement `saved`, qui revalide le cache du front.
    protected $touches = ['page'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Réglages de présentation du schéma : valeur enregistrée si elle est encore valide, sinon valeur par défaut. */
    public function resolvedSettings(): array
    {
        return BlockSchema::cleanSettings($this->definition()['settings'] ?? [], $this->settings);
    }

    /** Journal d'activité : type de bloc et page. */
    public function activityLabel(): string
    {
        return ($this->definition()['label'] ?? $this->type).' — '.($this->page?->getTranslation('title', config('cms.default_locale')) ?? '?');
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /** Définition du bloc dans config/cms.php, null si le type n'existe plus. */
    public function definition(): ?array
    {
        return config("cms.blocks.{$this->type}");
    }

    /** Contenu d'une langue tel que saisi, sans repli. */
    public function contentFor(string $locale): array
    {
        $value = $this->getTranslation('content', $locale, false);

        return is_array($value) ? $value : [];
    }

    /** Identifiants des médias utilisés par la section, toutes langues confondues. */
    public function imageIds(): array
    {
        $fields = $this->definition()['fields'] ?? [];

        return array_values(array_unique(array_merge(
            BlockSchema::imageIds($this->definition()['settings'] ?? [], $this->resolvedSettings()), // image de fond
            ...array_map(
                fn (string $locale) => BlockSchema::imageIds($fields, $this->contentFor($locale)),
                array_keys(config('cms.locales')),
            ),
        )));
    }

    /**
     * Contenu d'une langue complété champ par champ par la langue par défaut. Une liste (repeater) est une
     * traduction de la liste française : chaque élément est complété par l'élément de même rang (un élément
     * laissé vide reprend l'élément français en entier) ; les éléments en plus de la liste française sont gardés.
     */
    public function resolvedContent(string $locale): array
    {
        $own = static::withoutEmpty($this->contentFor($locale));
        $default = config('cms.default_locale');

        return static::withoutEmptyItems($locale === $default ? $own : static::mergeFallback($own, static::withoutEmpty($this->contentFor($default))));
    }

    private static function mergeFallback(array $own, array $fallback): array
    {
        if (array_is_list($own) && array_is_list($fallback)) {
            foreach ($fallback as $index => $value) {
                if (! array_key_exists($index, $own) || $own[$index] === []) {
                    $own[$index] = $value;
                } elseif (is_array($value) && is_array($own[$index])) {
                    $own[$index] = static::mergeFallback($own[$index], $value);
                }
            }
            ksort($own);

            return array_values($own);
        }

        foreach ($fallback as $key => $value) {
            if (! array_key_exists($key, $own)) {
                $own[$key] = $value;
            } elseif (is_array($value) && is_array($own[$key])) {
                $own[$key] = static::mergeFallback($own[$key], $value);
            }
        }

        return $own;
    }

    /**
     * Retire les chaînes vides et les listes vides, pour que le front applique ses textes par défaut.
     * Dans une liste, un élément vide garde sa place (vide = élément de la langue par défaut) ; ceux de fin sont retirés.
     */
    private static function withoutEmpty(array $data): array
    {
        if (array_is_list($data)) {
            $items = array_map(fn ($value) => is_array($value) ? static::withoutEmpty($value) : (static::isBlank($value) ? [] : $value), $data);
            while ($items !== [] && end($items) === []) {
                array_pop($items);
            }

            return $items;
        }

        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = static::withoutEmpty($value);
            }
            if ($value !== [] && ! static::isBlank($value)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /** Éléments de liste restés vides après le repli (aucun élément de même rang dans la langue par défaut). */
    private static function withoutEmptyItems(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $value = is_array($value) ? static::withoutEmptyItems($value) : $value;
            if ($value !== []) {
                $out[$key] = $value;
            }
        }

        return array_is_list($data) ? array_values($out) : $out;
    }

    private static function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
