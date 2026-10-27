<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Avis publiés du mur /recommandations. Contrat lu par immo-front/src/server/cms-api.ts (type TestimonialView). */
class TestimonialController extends Controller
{
    /** GET /api/v1/testimonials?locale=fr — citation et résultat dans la langue demandée, sinon en français. */
    public function index(Request $request): JsonResponse
    {
        $locale = $request->query('locale');
        $locale = is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');

        $items = Testimonial::published()->with('photo')->orderBy('id')->get()->map(fn (Testimonial $t) => [
            'id' => (string) $t->id,
            'name' => $t->client_name,
            'role' => (string) $t->role,
            'company' => (string) $t->company,
            'pole' => (string) $t->pole,
            'quote' => (string) $t->getTranslation('content', $locale),
            'metric' => (string) $t->getTranslation('metric', $locale),
            'photo' => $t->photo?->toApi($locale),
        ]);

        return response()->json(['data' => $items->filter(fn (array $t) => $t['quote'] !== '')->values()]);
    }
}
