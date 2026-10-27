<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /** GET /api/v1/pages — slugs des pages publiées (plan du site du front). */
    public function index(): JsonResponse
    {
        $pages = Page::published()->with('publishedRevision')->orderBy('slug')->get();

        // Date et indexation de la version en ligne (pas du brouillon).
        return response()->json(['data' => $pages->map(fn (Page $page) => [
            'slug' => $page->slug,
            'updated_at' => $page->publishedRevision->created_at?->toIso8601String(),
            'noindex' => $page->publishedRevision->fullSnapshot()['noindex'],
        ])]);
    }

    /** GET /api/v1/pages/{slug}?locale=fr — version publiée de la page et ses sections visibles, dans l'ordre. */
    public function show(Request $request, string $slug): PageResource
    {
        $locale = $request->query('locale');
        $locale = is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');

        // Version publiée (révision) : les modifications en brouillon n'apparaissent qu'à la publication.
        $page = Page::published()->with('publishedRevision')->where('slug', $slug)->firstOrFail();

        return new PageResource($page->publicVersion(), $locale);
    }
}
