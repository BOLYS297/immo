<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Articles Tech Insights publiés. Contrat lu par immo-front/src/server/cms-api.ts (type PostView). */
class PostController extends Controller
{
    /** GET /api/v1/posts?locale=fr — du plus récent au plus ancien, sans le corps. */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->locale($request);
        $posts = Post::published()->latest('published_at')->orderBy('slug')->get();

        return response()->json(['data' => $posts->map(fn (Post $post) => $post->toApi($locale))->values()]);
    }

    /** GET /api/v1/posts/{slug}?locale=fr */
    public function show(Request $request, string $slug): JsonResponse
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $post->toApi($this->locale($request), withBody: true)]);
    }

    private function locale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');
    }
}
