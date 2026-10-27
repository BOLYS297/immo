<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Testimonial;
use App\Services\FrontRevalidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

/** Médiathèque : images utilisables dans les champs « image » des sections (config/cms.php, clé media). */
class MediaController extends Controller
{
    /** Page de la médiathèque, ou JSON pour le sélecteur d'image des formulaires (?picker=1). */
    public function index(Request $request): View|JsonResponse
    {
        $media = Media::query()
            ->when($request->filled('q'), fn ($q) => $q->where('original_name', 'like', '%'.$request->string('q')->trim().'%'))
            ->latest()
            ->paginate($request->boolean('picker') ? 24 : 30)
            ->withQueryString();

        if ($request->boolean('picker')) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (Media $m) => $this->pickerItem($m)),
                'next' => $media->nextPageUrl(),
            ]);
        }

        return view('admin.media.index', ['media' => $media, 'search' => $request->query('q')]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $config = config('cms.media');
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'image', 'mimes:'.implode(',', $config['mimes']), 'max:'.$config['max_kb'], "dimensions:max_width={$config['max_dimension']},max_height={$config['max_dimension']}"],
        ], [
            'files.*.mimes' => 'Formats acceptés : '.implode(', ', $config['mimes']).'.',
            'files.*.max' => 'Une image ne doit pas dépasser '.round($config['max_kb'] / 1024).' Mo.',
            'files.*.dimensions' => "Une image ne doit pas dépasser {$config['max_dimension']} pixels de côté.",
        ], ['files.*' => 'image']);

        $created = collect($request->file('files'))->map(fn (UploadedFile $file) => $this->storeFile($file, $config));

        if ($request->expectsJson()) {
            return response()->json(['data' => $created->map(fn (Media $m) => $this->pickerItem($m))], 201);
        }

        return back()->with('success', $created->count() > 1 ? "{$created->count()} images ajoutées." : 'Image ajoutée.');
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $locales = array_keys(config('cms.locales'));
        $validated = $request->validate(collect($locales)->mapWithKeys(fn ($l) => ["alt.{$l}" => ['nullable', 'string', 'max:255']])->all());

        $media->replaceTranslations('alt', collect($locales)->mapWithKeys(fn ($l) => [$l => trim((string) ($validated['alt'][$l] ?? ''))])->filter()->all());
        $media->save();

        // Le texte alternatif est envoyé avec les pages : vider le cache de celles qui utilisent l'image.
        // Version en ligne seulement : un brouillon n'est pas en cache.
        FrontRevalidator::tags(...Page::publishedUsing($media->id)->map(fn (Page $page) => "page:{$page->slug}")->all(), ...(Testimonial::where('photo_id', $media->id)->exists() ? ['testimonials'] : []));

        return back()->with('success', 'Texte alternatif enregistré.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        // Brouillons et versions en ligne : une image retirée du brouillon peut encore être affichée sur le site.
        $default = config('cms.default_locale');
        $where = $media->usages()->toBase()->map(fn (PageSection $s) => '« '.$s->page->getTranslation('title', $default).' » ('.($s->definition()['label'] ?? $s->type).')')
            ->merge(Testimonial::where('photo_id', $media->id)->get()->map(fn (Testimonial $t) => '« '.$t->client_name.' » (photo d\'un avis)'))
            ->merge(Page::where('og_image_id', $media->id)->get()->map(fn (Page $page) => '« '.$page->getTranslation('title', $default).' » (image de partage)'))
            ->merge(Page::publishedUsing($media->id)->map(fn (Page $page) => '« '.$page->getTranslation('title', $default).' » (version en ligne)'))
            ->unique();
        if ($where->isNotEmpty()) {
            return back()->with('error', 'Image utilisée dans : '.$where->implode(', ').'. Retirez-la de ces sections (et publiez la page) avant de la supprimer.');
        }

        $media->delete(); // supprime aussi le fichier

        return back()->with('success', 'Image supprimée.');
    }

    private function storeFile(UploadedFile $file, array $config): Media
    {
        [$width, $height] = getimagesize($file->getRealPath()) ?: [0, 0];
        $path = $file->store($config['directory'].'/'.now()->format('Y/m'), ['disk' => $config['disk'], 'visibility' => 'public']);

        return Media::create([
            'disk' => $config['disk'],
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => Auth::id(),
        ]);
    }

    private function pickerItem(Media $m): array
    {
        return ['id' => $m->id, 'url' => $m->url(), 'name' => $m->original_name, 'width' => $m->width, 'height' => $m->height, 'alt' => (string) $m->getTranslation('alt', config('cms.default_locale'))];
    }
}
