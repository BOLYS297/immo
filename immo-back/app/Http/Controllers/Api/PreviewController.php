<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\Post;
use App\Services\Preview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/v1/preview/{jeton}?locale=fr — contenu d'un aperçu demandé depuis l'admin (brouillon, révision ou article). */
class PreviewController extends Controller
{
    public function show(Request $request, string $token): JsonResponse
    {
        $payload = Preview::get($token) ?? abort(404);
        $locale = $request->query('locale');
        $locale = is_string($locale) && array_key_exists($locale, config('cms.locales')) ? $locale : config('cms.default_locale');

        if ($payload['kind'] === 'post') {
            $post = Post::findOrFail($payload['post_id']);

            return response()->json(['data' => ['kind' => 'post', 'published' => $post->is_published, 'post' => $post->toApi($locale, withBody: true)]]);
        }

        $page = Page::findOrFail($payload['page_id']);
        $revision = $payload['revision_id'] ? $page->revisions()->find($payload['revision_id']) ?? abort(404) : null;
        $version = $revision ? $page->revisionVersion($revision) : $page->draftVersion($payload['overrides'] ?? []);

        return response()->json(['data' => [
            'kind' => 'page',
            'version' => $revision ? 'revision' : 'draft',
            'revisionDate' => $revision?->created_at?->toIso8601String(),
            'page' => (new PageResource($version, $locale))->resolve($request),
        ]]);
    }
}
