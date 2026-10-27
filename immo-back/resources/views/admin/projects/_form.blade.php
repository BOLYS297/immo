{{-- Réalisation bilingue, partagée par create et edit. Attend $project. La page inclut admin.media._picker. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($default = config('cms.default_locale'))

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <label for="client_name" class="form-label text-white small fw-semibold">Client</label>
        <input type="text" name="client_name" id="client_name" maxlength="120" class="{{ $inputClass }}" value="{{ old('client_name', $project->client_name) }}">
    </div>
    <div class="col-md-3">
        <label for="completion_date" class="form-label text-white small fw-semibold">Date de livraison</label>
        <input type="date" name="completion_date" id="completion_date" class="{{ $inputClass }}" value="{{ old('completion_date', $project->completion_date?->toDateString()) }}">
    </div>
    <div class="col-md-4">
        <label for="demo_url" class="form-label text-white small fw-semibold">Lien (site, application)</label>
        <input type="url" name="demo_url" id="demo_url" maxlength="255" class="{{ $inputClass }}" value="{{ old('demo_url', $project->demo_url) }}" placeholder="https://…">
    </div>
    <div class="col-md-8">
        <label for="technologies" class="form-label text-white small fw-semibold">Technologies</label>
        <input type="text" name="technologies" id="technologies" maxlength="300" class="{{ $inputClass }}" value="{{ old('technologies', implode(', ', (array) ($project->technologies ?? []))) }}" placeholder="Next.js, Laravel, n8n" aria-describedby="technologies_help">
        <div id="technologies_help" class="form-text text-secondary">Séparées par des virgules.</div>
    </div>
    <div class="col-md-2">
        <label for="sort_order" class="form-label text-white small fw-semibold">Ordre</label>
        <input type="number" name="sort_order" id="sort_order" min="0" max="999" class="{{ $inputClass }}" value="{{ old('sort_order', $project->sort_order) }}" required>
    </div>
    <div class="col-md-2">
        <label for="slug" class="form-label text-white small fw-semibold">Identifiant</label>
        <input type="text" name="slug" id="slug" maxlength="80" class="{{ $inputClass }} font-monospace" value="{{ old('slug', $project->slug) }}" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="auto">
    </div>
    <div class="col-12">
        @include('admin.pages.sections._field', ['field' => ['type' => 'image', 'label' => 'Image (médiathèque)'], 'name' => 'image_id', 'id' => 'image_id', 'value' => old('image_id', $project->image_id)])
    </div>
</div>

@include('admin.partials._locale-tabs', ['prefix' => 'project'])
<div class="tab-content mb-4">
    @foreach(config('cms.locales') as $locale => $name)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="project-{{ $locale }}" role="tabpanel">
            @if($locale !== $default)<p class="form-text text-secondary mt-0 mb-3">Laissé vide, un champ reprend la version {{ config('cms.locales')[$default] }}.</p>@endif
            @include('admin.partials._translated-field', ['model' => $project, 'name' => 'title', 'label' => 'Titre', 'locale' => $locale, 'required' => true, 'max' => 200])
            @include('admin.partials._translated-field', ['model' => $project, 'name' => 'short_description', 'label' => 'Résumé', 'locale' => $locale, 'required' => true, 'type' => 'textarea', 'rows' => 2, 'max' => 600])
            <div class="row g-3">
                <div class="col-md-6">@include('admin.partials._translated-field', ['model' => $project, 'name' => 'problem_statement', 'label' => 'Contexte / besoin', 'locale' => $locale, 'type' => 'textarea', 'rows' => 4, 'max' => 2000])</div>
                <div class="col-md-6">@include('admin.partials._translated-field', ['model' => $project, 'name' => 'solution_statement', 'label' => 'Solution apportée', 'locale' => $locale, 'type' => 'textarea', 'rows' => 4, 'max' => 2000])</div>
            </div>
            @include('admin.partials._translated-field', ['model' => $project, 'name' => 'verified_results', 'label' => 'Résultats mesurés', 'locale' => $locale, 'type' => 'textarea', 'rows' => 2, 'max' => 1000, 'help' => 'Uniquement des résultats réels, mesurés et validés par le client.'])
        </div>
    @endforeach
</div>

<fieldset class="p-3 mb-4 rounded-3" style="background: var(--surface-2);">
    <legend class="form-label small fw-semibold mb-2 float-none w-auto">Publication</legend>
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label for="client_consent_at" class="form-label small">Date de l'accord écrit du client</label>
            <input type="date" name="client_consent_at" id="client_consent_at" class="{{ $inputClass }}" value="{{ old('client_consent_at', $project->client_consent_at?->toDateString()) }}" max="{{ now()->toDateString() }}" aria-describedby="consent_help">
        </div>
        <div class="col-md-7">
            <div class="form-check form-switch mb-2">
                <input type="hidden" name="is_published" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" @checked(old('is_published', $project->is_published))>
                <label class="form-check-label small" for="is_published">En ligne</label>
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="is_featured" id="is_featured" value="1" @checked(old('is_featured', $project->is_featured))>
                <label class="form-check-label small" for="is_featured">Mise en avant</label>
            </div>
        </div>
    </div>
    <div id="consent_help" class="form-text text-secondary">Obligatoire pour mettre en ligne : on ne publie le nom d'un client, son projet et ses résultats qu'avec son accord écrit (à conserver).</div>
</fieldset>
