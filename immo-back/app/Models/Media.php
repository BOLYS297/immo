<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;

/** Image de la médiathèque, utilisable dans les champs « image » des sections. */
class Media extends Model
{
    use HasTranslations, RecordsActivity;

    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'width', 'height', 'alt', 'uploaded_by'];

    public array $translatable = ['alt'];

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (Media $media) => Storage::disk($media->disk)->delete($media->path));
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /** Forme envoyée au front (immo-front/src/lib/blocks.ts, type MediaImage). */
    public function toApi(string $locale): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'width' => $this->width,
            'height' => $this->height,
            'alt' => (string) $this->getTranslation('alt', $locale),
        ];
    }

    /** Sections qui utilisent cette image (champs « image » de leur schéma, toutes langues). */
    public function usages()
    {
        return PageSection::with('page')
            ->where(fn ($q) => $q->where('content', 'like', '%"'.$this->id.'"%')->orWhere('settings', 'like', '%"'.$this->id.'"%'))
            ->get()
            ->filter(fn (PageSection $section) => in_array((string) $this->id, $section->imageIds(), true))
            ->values();
    }
}
