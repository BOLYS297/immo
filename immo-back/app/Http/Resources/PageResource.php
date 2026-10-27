<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\PageSection;
use App\Services\BlockSchema;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrat JSON lu par immo-front/src/server/cms-api.ts — garder les deux alignés.
 */
class PageResource extends JsonResource
{
    public function __construct($resource, private readonly string $locale)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $text = fn (string $key) => $this->getTranslation($key, $this->locale) ?: null;

        return [
            'slug' => $this->slug,
            'locale' => $this->locale,
            'title' => $text('title'),
            'meta' => [
                'title' => $text('meta_title'),
                'description' => $text('meta_description'),
                'image' => $this->og_image_id ? Media::find($this->og_image_id)?->toApi($this->locale) : null,
                'noindex' => (bool) $this->noindex,
            ],
            'sections' => $this->sectionsPayload(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Sections visibles, contenu résolu dans la langue demandée. Un type retiré de config/cms.php n'est plus envoyé
     * (le front ne saurait pas l'afficher). Les champs « image » passent de l'identifiant du média à
     * {id, url, width, height, alt} ; une image supprimée de la médiathèque disparaît du contenu.
     */
    private function sectionsPayload(): array
    {
        $sections = $this->sections
            ->filter(fn (PageSection $section) => $section->definition() !== null)
            ->map(fn (PageSection $section) => [$section, $section->resolvedContent($this->locale)])
            ->values();

        $ids = $sections->flatMap(fn (array $s) => [
            ...BlockSchema::imageIds($s[0]->definition()['fields'], $s[1]),
            ...BlockSchema::imageIds($s[0]->definition()['settings'] ?? [], $s[0]->resolvedSettings()),
        ])->unique();
        $media = $ids->isEmpty() ? collect() : Media::whereIn('id', $ids)->get()->keyBy('id');

        return $sections->map(fn (array $s) => [
            'id' => $s[0]->id,
            'type' => $s[0]->type,
            'content' => (object) BlockSchema::mapImages($s[0]->definition()['fields'], $s[1], fn (string $id) => $media->get((int) $id)?->toApi($this->locale)),
            'settings' => (object) BlockSchema::mapImages($s[0]->definition()['settings'] ?? [], $s[0]->resolvedSettings(), fn (string $id) => $media->get((int) $id)?->toApi($this->locale)),
        ])->all();
    }
}
