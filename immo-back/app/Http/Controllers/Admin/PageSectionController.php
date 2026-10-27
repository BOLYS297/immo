<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\Activity;
use App\Services\BlockSchema;
use App\Services\Preview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageSectionController extends Controller
{
    public function store(Request $request, Page $page): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(config('cms.blocks')))],
        ]);

        // Contenu vide : le front affiche ses textes par défaut tant que la section n'est pas rédigée.
        $section = $page->sections()->create([
            'type' => $validated['type'],
            'is_visible' => true,
            'sort_order' => ($page->sections()->max('sort_order') ?? -1) + 1,
        ]);

        return redirect()->route('admin.sections.edit', $section)->with('success', 'Section ajoutée en fin de page.');
    }

    public function edit(PageSection $section): View
    {
        abort_unless($section->definition(), 404, 'Ce type de section n\'existe plus dans config/cms.php.');
        $section->load('page');

        // Aperçu en direct : brouillon de la page, mis à jour pendant la saisie (live()).
        $token = Preview::create(['kind' => 'page', 'page_id' => $section->page_id, 'revision_id' => null, 'overrides' => []]);

        return view('admin.pages.sections.edit', [
            'section' => $section,
            'block' => $section->definition(),
            'locales' => config('cms.locales'),
            'previewToken' => $token,
            'previewUrl' => Preview::url($token, "section-{$section->id}"),
        ]);
    }

    public function update(Request $request, PageSection $section): RedirectResponse
    {
        $data = $this->validatedSection($request, $section);
        $section->setTranslations('content', $data['content']);
        $section->settings = $data['settings'];
        $section->is_visible = $data['is_visible'];
        $section->save();

        return redirect()->route('admin.pages.edit', $section->page_id)->with('success', 'Section enregistrée en brouillon. Publiez la page pour la mettre en ligne.');
    }

    /** Aperçu en direct : contenu saisi mais pas encore enregistré, envoyé par la page d'édition (JSON). */
    public function live(Request $request, PageSection $section): JsonResponse
    {
        $request->validate(['preview_token' => ['required', 'string']]);
        $payload = Preview::get($request->input('preview_token'));
        if (! $payload || $payload['kind'] !== 'page' || $payload['page_id'] !== $section->page_id) {
            return response()->json(['message' => 'Aperçu expiré : rechargez la page.'], 410);
        }
        Preview::override($request->input('preview_token'), $section->id, $this->validatedSection($request, $section));

        return response()->json(['ok' => true]);
    }

    public function destroy(PageSection $section): RedirectResponse
    {
        $section->delete();

        return redirect()->route('admin.pages.edit', $section->page_id)->with('success', 'Section supprimée.');
    }

    public function visibility(PageSection $section): RedirectResponse
    {
        $section->update(['is_visible' => ! $section->is_visible]);

        return back()->with('success', $section->is_visible ? 'Section affichée sur le site.' : 'Section masquée du site.');
    }

    /** Glisser-déposer : nouvel ordre complet des sections de la page (identifiants, du haut vers le bas). */
    public function reorder(Request $request, Page $page): JsonResponse
    {
        $current = $page->sections()->pluck('id')->all();
        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:'.count($current)],
            'ids.*' => ['integer', 'distinct', Rule::in($current)],
        ]);

        Activity::record('reordered', $page);
        DB::transaction(function () use ($validated, $page) {
            foreach (array_values($validated['ids']) as $position => $id) {
                PageSection::whereKey($id)->update(['sort_order' => $position]);
            }
            $page->touch();
        });

        return response()->json(['ok' => true]);
    }

    /** Copie d'une section (contenu de toutes les langues, réglages, visibilité), placée juste après l'originale. */
    public function duplicate(PageSection $section): RedirectResponse
    {
        Activity::record('duplicated', $section);
        Activity::quietly(fn () => DB::transaction(function () use ($section) {
            $ids = $section->page->sections()->pluck('id')->all();
            $copy = $section->replicate();
            $copy->save();
            array_splice($ids, array_search($section->id, $ids, true) + 1, 0, [$copy->id]);
            foreach ($ids as $position => $id) {
                PageSection::whereKey($id)->update(['sort_order' => $position]);
            }
        }));

        return back()->with('success', 'Section dupliquée juste en dessous (en brouillon).');
    }

    public function move(Request $request, PageSection $section): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        DB::transaction(function () use ($section, $validated) {
            $ids = $section->page->sections()->pluck('id')->all();
            $from = array_search($section->id, $ids, true);
            $to = $from + ($validated['direction'] === 'up' ? -1 : 1);
            if (! isset($ids[$to])) {
                return;
            }
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
            // Réindexation complète : corrige aussi d'éventuels ordres en double.
            foreach ($ids as $position => $id) {
                PageSection::whereKey($id)->update(['sort_order' => $position]);
            }
            $section->page->touch();
        });

        return back();
    }

    /** Contenu, réglages et visibilité validés et nettoyés selon le schéma du bloc (config/cms.php). */
    private function validatedSection(Request $request, PageSection $section): array
    {
        $definition = $section->definition() ?? abort(404);
        $fields = $definition['fields'];
        $settings = $definition['settings'] ?? [];
        $request->validate(BlockSchema::rules($fields) + BlockSchema::settingsRules($settings), [
            'regex' => 'Le champ « :attribute » doit être un chemin du site (ex. /contact) ou une adresse http(s).',
        ], BlockSchema::attributes($fields) + BlockSchema::settingsAttributes($settings));

        return [
            'content' => BlockSchema::clean($fields, $request->input('content', [])),
            'settings' => $settings ? BlockSchema::cleanSettings($settings, $request->input('settings')) : null,
            'is_visible' => $request->boolean('is_visible'),
        ];
    }
}
