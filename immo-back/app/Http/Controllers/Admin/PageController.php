<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Services\Preview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::withCount('sections')->with('publishedRevision')->orderBy('slug')->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.create', ['page' => new Page(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page créée. Ajoutez-lui maintenant des sections.');
    }

    public function edit(Page $page): View
    {
        $page->load(['sections', 'publishedRevision']);
        $blocks = config('cms.blocks');
        $revisions = $page->revisions()->with('author')->limit(10)->get();

        return view('admin.pages.edit', compact('page', 'blocks', 'revisions'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Réglages enregistrés. Le titre et le SEO seront en ligne à la prochaine publication.');
    }

    /** Met le brouillon en ligne (nouvelle révision). */
    public function publish(Request $request, Page $page): RedirectResponse
    {
        $page->publish($request->user());

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page publiée : le site affiche maintenant cette version.');
    }

    /** Annule les modifications non publiées : le brouillon reprend la version en ligne. */
    public function discard(Page $page): RedirectResponse
    {
        $page->publishedRevision ?? abort(404);
        $page->restore($page->publishedRevision);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Modifications annulées : le brouillon est revenu à la version en ligne.');
    }

    /** Reprend une ancienne version comme brouillon (le site ne change qu'à la publication). */
    public function restore(Page $page, PageRevision $revision): RedirectResponse
    {
        $page->restore($revision);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Version du '.$revision->created_at->format('d/m/Y à H:i').' restaurée en brouillon. Vérifiez-la dans l\'aperçu, puis publiez.');
    }

    /** Ouvre l'aperçu du brouillon (ou d'une révision, ?revision=id) sur le front. */
    public function preview(Request $request, Page $page): RedirectResponse
    {
        $revision = $request->filled('revision') ? $page->revisions()->findOrFail($request->integer('revision')) : null;

        return redirect()->away(Preview::url(Preview::create(['kind' => 'page', 'page_id' => $page->id, 'revision_id' => $revision?->id, 'overrides' => []])));
    }

    public function destroy(Page $page): RedirectResponse
    {
        Gate::authorize('pages.publish'); // retire une page du site
        if ($page->isSystem()) {
            return back()->with('error', 'Cette page fait partie du site (menus, liens) : elle ne peut pas être supprimée. Vous pouvez en modifier les sections.');
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page supprimée.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $default = config('cms.default_locale');
        $rules = [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page)],
            'is_published' => ['boolean'],
            'og_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'noindex' => ['boolean'],
        ];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $rules["title.{$locale}"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["meta_title.{$locale}"] = ['nullable', 'string', 'max:255'];
            $rules["meta_description.{$locale}"] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules, [
            'slug.regex' => 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets (ex. « a-propos »).',
        ]);

        // Toutes les langues, '' pour un champ vide : sinon vider un champ dans l'admin conserverait l'ancienne valeur.
        $translations = fn (string $key) => collect(array_keys(config('cms.locales')))->mapWithKeys(fn ($l) => [$l => trim((string) ($validated[$key][$l] ?? ''))])->all();

        // Page système : adresse et publication fixes, seuls les textes et le SEO se modifient.
        if ($page?->isSystem()) {
            $validated['slug'] = $page->slug;
        }

        return [
            'slug' => $validated['slug'],
            'title' => $translations('title'),
            'meta_title' => $translations('meta_title'),
            'meta_description' => $translations('meta_description'),
            'og_image_id' => $validated['og_image_id'] ?? null,
            'noindex' => $request->boolean('noindex'),
            // Mettre en ligne ou hors ligne : droit de publication ; sinon l'état ne change pas.
            'is_published' => match (true) {
                (bool) $page?->isSystem() => true,
                Gate::allows('pages.publish') => $request->boolean('is_published'),
                default => $page?->is_published ?? true,
            },
        ];
    }
}
