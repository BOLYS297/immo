<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Preview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Articles Tech Insights du front, en français et en anglais.
 * Rédacteur (posts.write) : ses propres brouillons seulement ; la publication demande posts.publish.
 */
class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::with('author')
            ->unless(Gate::allows('posts.manage'), fn ($query) => $query->where('user_id', Auth::id()))
            ->latest('published_at')->latest('created_at')->paginate(15);

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.posts.create', ['post' => new Post(['reading_minutes' => 5, 'published_at' => now(), 'is_published' => false])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $post = Post::create($this->validated($request) + ['user_id' => Auth::id()]);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Article créé.');
    }

    public function edit(Post $post): View
    {
        Gate::authorize('posts.edit', $post);

        return view('admin.posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        Gate::authorize('posts.edit', $post);
        $post->update($this->validated($request, $post));

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Article mis à jour.');
    }

    /** Aperçu de l'article sur le front, même non publié : son auteur ou un gestionnaire des articles. */
    public function preview(Request $request, Post $post): RedirectResponse
    {
        abort_unless(Gate::allows('posts.manage') || $post->user_id === $request->user()->id, 403);

        return redirect()->away(Preview::url(Preview::create(['kind' => 'post', 'post_id' => $post->id])));
    }

    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('posts.edit', $post);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Article supprimé.');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $default = config('cms.default_locale');
        $rules = [
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($post)],
            'published_at' => ['required', 'date'],
            'reading_minutes' => ['required', 'integer', 'between:1,60'],
            'is_published' => ['boolean'],
        ];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $required = $locale === $default ? 'required' : 'nullable';
            $rules["title.{$locale}"] = [$required, 'string', 'max:200'];
            $rules["summary.{$locale}"] = [$required, 'string', 'max:400'];
            $rules["content.{$locale}"] = [$required, 'string', 'max:20000'];
            $rules["tags.{$locale}"] = ['nullable', 'string', 'max:300'];
            $rules["meta_title.{$locale}"] = ['nullable', 'string', 'max:255'];
            $rules["meta_description.{$locale}"] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules, [
            'slug.regex' => 'L\'adresse ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
        ], [
            "title.{$default}" => 'titre (FR)', "summary.{$default}" => 'résumé (FR)', "content.{$default}" => 'texte (FR)',
        ]);

        // Toutes les langues sont envoyées, '' pour un champ vide : spatie la traite comme absente, et vider
        // un champ dans l'admin efface bien l'ancienne valeur (une langue omise serait conservée telle quelle).
        $locales = array_keys(config('cms.locales'));
        $translations = fn (string $key) => collect($locales)->mapWithKeys(fn ($l) => [$l => trim((string) ($validated[$key][$l] ?? ''))])->all();
        $tags = array_map(
            fn (string $list) => array_slice(array_values(array_filter(array_map(fn ($t) => Str::limit(trim($t), 40, ''), explode(',', $list)))), 0, 6),
            $translations('tags'),
        );

        return [
            'slug' => ($validated['slug'] ?? null) ?: ($post?->slug ?? $this->uniqueSlug($validated['title'][$default])),
            'title' => $translations('title'),
            'summary' => $translations('summary'),
            'content' => $translations('content'),
            'tags' => $tags,
            'meta_title' => $translations('meta_title'),
            'meta_description' => $translations('meta_description'),
            'published_at' => $validated['published_at'],
            'reading_minutes' => $validated['reading_minutes'],
            // Sans droit de publication, l'état publié ne change pas (brouillon à la création).
            'is_published' => Gate::allows('posts.publish') ? $request->boolean('is_published') : (bool) $post?->is_published,
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 70, '') ?: 'article';
        $slug = $base;
        for ($i = 2; Post::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
