<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TranslatedInput;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Services (domaines d'expertise) du site, en français et en anglais. */
class ServiceController extends Controller
{
    use TranslatedInput;

    public function index(): View
    {
        $services = Service::orderByDesc('is_published')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('admin.services.create', ['service' => new Service(['is_published' => true, 'icon' => 'workflow', 'sort_order' => (Service::max('sort_order') ?? -1) + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Service::create($this->validated($request));

        return redirect()->route('admin.services.index')->with('success', 'Service créé.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request, $service));

        return redirect()->route('admin.services.index')->with('success', 'Service mis à jour.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service supprimé.');
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $default = config('cms.default_locale');
        $validated = $request->validate([
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('services', 'slug')->ignore($service)],
            'icon' => ['required', Rule::in(array_keys(config('cms.service_icons')))],
            'quote_domain' => ['nullable', Rule::in(['ia', 'web', 'infra'])],
            'sort_order' => ['required', 'integer', 'between:0,999'],
            'is_published' => ['boolean'],
        ] + $this->translatedRules([
            'title' => ['string', 'max:160'],
            'short_description' => ['string', 'max:500'],
            'deliverables' => ['string', 'max:1500'],
            'cta_label' => ['string', 'max:80'],
        ], ['title', 'short_description']), [
            'slug.regex' => 'L\'identifiant ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
        ], $this->translatedAttributes(['title' => 'titre', 'short_description' => 'description', 'deliverables' => 'livrables', 'cta_label' => 'bouton']));

        return [
            'slug' => ($validated['slug'] ?? null) ?: ($service?->slug ?? $this->uniqueSlug($validated['title'][$default])),
            'icon' => $validated['icon'],
            'quote_domain' => $validated['quote_domain'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published'),
            'title' => $this->translations($validated, 'title'),
            'short_description' => $this->translations($validated, 'short_description'),
            'deliverables' => $this->translations($validated, 'deliverables', lines: true),
            'cta_label' => $this->translations($validated, 'cta_label'),
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 70, '') ?: 'service';
        $slug = $base;
        for ($i = 2; Service::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
