<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Contenus structurés publiés (services, réalisations, FAQ). Contrats lus par immo-front/src/server/cms-api.ts. */
class StructuredContentController extends Controller
{
    /** GET /api/v1/services?locale=fr */
    public function services(Request $request): JsonResponse
    {
        $locale = $this->locale($request);

        return response()->json(['data' => Service::published()->orderBy('sort_order')->orderBy('id')->get()->map(fn (Service $s) => $s->toApi($locale))->values()]);
    }

    /** GET /api/v1/projects?locale=fr — mises en avant d'abord, puis ordre choisi, puis plus récentes. */
    public function projects(Request $request): JsonResponse
    {
        $locale = $this->locale($request);
        $projects = Project::published()->with('image')->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('completion_date')->get();

        return response()->json(['data' => $projects->map(fn (Project $p) => $p->toApi($locale))->values()]);
    }

    /** GET /api/v1/faqs?locale=fr — toutes les catégories ; le front filtre (une seule entrée de cache). */
    public function faqs(Request $request): JsonResponse
    {
        $locale = $this->locale($request);

        return response()->json(['data' => Faq::published()->get()->map(fn (Faq $f) => $f->toApi($locale))->values()]);
    }

    private function locale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');
    }
}
