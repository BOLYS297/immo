<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\Activity;
use App\Services\BlockSchema;
use App\Services\FrontRevalidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(): View
    {
        $menus = collect(config('cms.menus'))->map(fn (array $menu, string $key) => $menu + ['items' => MenuItem::tree($key)]);

        return view('admin.menus.index', ['menus' => $menus, 'locales' => config('cms.locales')]);
    }

    public function store(Request $request, string $menu): RedirectResponse
    {
        $config = $this->menu($menu);
        $data = $this->validated($request, $menu, $config);
        $data['sort_order'] = (MenuItem::where('menu', $menu)->where('parent_id', $data['parent_id'])->max('sort_order') ?? -1) + 1;
        MenuItem::create($data + ['menu' => $menu]);

        return redirect()->route('admin.menus.index')->withFragment("menu-{$menu}")->with('success', 'Élément ajouté au menu « '.$config['label'].' ».');
    }

    public function edit(MenuItem $item): View
    {
        $config = $this->menu($item->menu);

        return view('admin.menus.edit', [
            'item' => $item,
            'config' => $config,
            'columns' => $this->columns($item->menu, $config, $item),
            'locales' => config('cms.locales'),
        ]);
    }

    public function update(Request $request, MenuItem $item): RedirectResponse
    {
        $data = $this->validated($request, $item->menu, $this->menu($item->menu), $item);
        if ($data['parent_id'] !== $item->parent_id) {
            $data['sort_order'] = (MenuItem::where('menu', $item->menu)->where('parent_id', $data['parent_id'])->max('sort_order') ?? -1) + 1;
        }
        $item->update($data);

        return redirect()->route('admin.menus.index')->withFragment("menu-{$item->menu}")->with('success', 'Élément de menu enregistré.');
    }

    public function destroy(MenuItem $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('admin.menus.index')->withFragment("menu-{$item->menu}")->with('success', 'Élément supprimé du menu.');
    }

    public function move(Request $request, MenuItem $item): RedirectResponse
    {
        $validated = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        DB::transaction(function () use ($item, $validated) {
            $ids = $item->siblings()->pluck('id')->all();
            $from = array_search($item->id, $ids, true);
            $to = $from + ($validated['direction'] === 'up' ? -1 : 1);
            if (! isset($ids[$to])) {
                return;
            }
            [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
            foreach ($ids as $position => $id) {
                MenuItem::whereKey($id)->update(['sort_order' => $position]);
            }
        });
        // Mises à jour en masse : pas d'événement « saved », on prévient le front ici.
        FrontRevalidator::tags('site');

        return redirect()->route('admin.menus.index')->withFragment("menu-{$item->menu}");
    }

    /** Glisser-déposer : nouvel ordre complet d'un niveau (mêmes emplacement et colonne parente). */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'distinct']]);
        $first = MenuItem::findOrFail($validated['ids'][0]);
        $siblings = $first->siblings()->pluck('id')->sort()->values()->all();
        $given = collect($validated['ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
        abort_unless($siblings === $given, 422, 'Ordre incomplet ou éléments de niveaux différents.');

        Activity::record('reordered', ['MenuItem', null, 'Menu « '.(config('cms.menus.'.$first->menu.'.label') ?? $first->menu).' »']);
        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $position => $id) {
                MenuItem::whereKey($id)->update(['sort_order' => $position]);
            }
        });
        FrontRevalidator::tags('site');

        return response()->json(['ok' => true]);
    }

    private function menu(string $menu): array
    {
        return config("cms.menus.{$menu}") ?? abort(404);
    }

    /** Colonnes du pied de page où ranger un lien (une colonne ne peut pas devenir le lien d'une autre). */
    private function columns(string $menu, array $config, ?MenuItem $except = null)
    {
        if ($config['depth'] < 2 || $except?->children()->exists()) {
            return collect();
        }

        return MenuItem::where('menu', $menu)->whereNull('parent_id')->when($except, fn ($q) => $q->whereKeyNot($except->id))->orderBy('sort_order')->get();
    }

    private function validated(Request $request, string $menu, array $config, ?MenuItem $item = null): array
    {
        $default = config('cms.default_locale');
        $columns = $this->columns($menu, $config, $item)->pluck('id')->all();
        // Pied de page : sans colonne parente, l'élément est lui-même une colonne (titre sans lien).
        $isColumn = $config['depth'] >= 2 && ! $request->filled('parent_id');

        $rules = [
            'parent_id' => ['nullable', 'integer', Rule::in($columns)],
            'url' => [$isColumn ? 'nullable' : 'required', 'string', 'max:255', BlockSchema::LINK],
            'display' => ['nullable', Rule::in(array_keys(config('cms.menu_display')))],
            'new_tab' => ['boolean'],
        ];
        foreach (array_keys(config('cms.locales')) as $locale) {
            $rules["label.{$locale}"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:80'];
        }

        $validated = $request->validate($rules, [
            'url.regex' => 'Le lien doit être un chemin du site (ex. /contact) ou une adresse http(s).',
            'parent_id.in' => 'Colonne inconnue.',
        ], ['label.fr' => 'libellé (FR)', 'label.en' => 'libellé (EN)', 'url' => 'lien']);

        return [
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'label' => collect(array_keys(config('cms.locales')))->mapWithKeys(fn ($l) => [$l => trim((string) ($validated['label'][$l] ?? ''))])->all(),
            'url' => $isColumn ? null : $validated['url'],
            'display' => $config['display'] ? ($validated['display'] ?? 'all') : 'all',
            'new_tab' => ! $isColumn && $request->boolean('new_tab'),
        ];
    }
}
