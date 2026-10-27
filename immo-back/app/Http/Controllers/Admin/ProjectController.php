<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TranslatedInput;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Réalisations (cas clients), en français et en anglais. Une réalisation nomme un client et ses résultats :
 * sa mise en ligne exige la date de l'accord écrit du client (même règle que les avis).
 */
class ProjectController extends Controller
{
    use TranslatedInput;

    public function index(): View
    {
        $projects = Project::with('image')->orderByDesc('is_published')->orderBy('sort_order')->orderByDesc('completion_date')->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.create', ['project' => new Project(['is_published' => false, 'sort_order' => (Project::max('sort_order') ?? -1) + 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Project::create($this->validated($request));

        return redirect()->route('admin.projects.index')->with('success', 'Réalisation créée.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validated($request, $project));

        return redirect()->route('admin.projects.index')->with('success', 'Réalisation mise à jour.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Réalisation supprimée.');
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        $default = config('cms.default_locale');
        $validated = $request->validate([
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('projects', 'slug')->ignore($project)],
            'client_name' => ['nullable', 'string', 'max:120'],
            'completion_date' => ['nullable', 'date'],
            'demo_url' => ['nullable', 'url:http,https', 'max:255'],
            'technologies' => ['nullable', 'string', 'max:300'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            'sort_order' => ['required', 'integer', 'between:0,999'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
            'client_consent_at' => ['nullable', 'date', 'before_or_equal:today', 'required_if_accepted:is_published'],
        ] + $this->translatedRules([
            'title' => ['string', 'max:200'],
            'short_description' => ['string', 'max:600'],
            'problem_statement' => ['string', 'max:2000'],
            'solution_statement' => ['string', 'max:2000'],
            'verified_results' => ['string', 'max:1000'],
        ], ['title', 'short_description']), [
            'slug.regex' => 'L\'identifiant ne peut contenir que des lettres minuscules, des chiffres et des tirets.',
            'client_consent_at.required_if_accepted' => 'Pour publier une réalisation, indiquez la date de l\'accord écrit du client.',
        ], ['client_consent_at' => 'date de l\'accord du client', 'demo_url' => 'lien'] + $this->translatedAttributes([
            'title' => 'titre', 'short_description' => 'résumé', 'problem_statement' => 'contexte', 'solution_statement' => 'solution', 'verified_results' => 'résultats',
        ]));

        return [
            'slug' => ($validated['slug'] ?? null) ?: ($project?->slug ?? $this->uniqueSlug($validated['title'][$default])),
            'client_name' => $validated['client_name'] ?? null,
            'completion_date' => $validated['completion_date'] ?? null,
            'demo_url' => $validated['demo_url'] ?? null,
            'technologies' => array_values(array_filter(array_map(fn ($t) => Str::limit(trim($t), 40, ''), explode(',', (string) ($validated['technologies'] ?? ''))), 'strlen')),
            'image_id' => $validated['image_id'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_featured' => $request->boolean('is_featured'),
            'is_published' => $request->boolean('is_published'),
            'client_consent_at' => $validated['client_consent_at'] ?? null,
            'title' => $this->translations($validated, 'title'),
            'short_description' => $this->translations($validated, 'short_description'),
            'problem_statement' => $this->translations($validated, 'problem_statement'),
            'solution_statement' => $this->translations($validated, 'solution_statement'),
            'verified_results' => $this->translations($validated, 'verified_results'),
        ];
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 70, '') ?: 'realisation';
        $slug = $base;
        for ($i = 2; Project::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
