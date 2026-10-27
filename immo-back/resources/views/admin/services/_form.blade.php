{{-- Service bilingue, partagé par create et edit. Attend $service. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($default = config('cms.default_locale'))

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label for="icon" class="form-label text-white small fw-semibold">Icône</label>
        <select name="icon" id="icon" class="form-select">
            @foreach(config('cms.service_icons') as $key => $label)
                <option value="{{ $key }}" @selected(old('icon', $service->icon) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="quote_domain" class="form-label text-white small fw-semibold">Bouton : pôle présélectionné</label>
        <select name="quote_domain" id="quote_domain" class="form-select" aria-describedby="quote_domain_help">
            <option value="">— Aucun —</option>
            @foreach(['ia' => 'IA & Automatisation', 'web' => 'Web & Mobile', 'infra' => 'Infra & Sécurité'] as $key => $label)
                <option value="{{ $key }}" @selected(old('quote_domain', $service->quote_domain) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <div id="quote_domain_help" class="form-text text-secondary">Le bouton mène au formulaire de chiffrage, pôle présélectionné.</div>
    </div>
    <div class="col-md-2">
        <label for="sort_order" class="form-label text-white small fw-semibold">Ordre</label>
        <input type="number" name="sort_order" id="sort_order" min="0" max="999" class="{{ $inputClass }}" value="{{ old('sort_order', $service->sort_order) }}" required>
    </div>
    <div class="col-md-2">
        <label for="slug" class="form-label text-white small fw-semibold">Identifiant</label>
        <input type="text" name="slug" id="slug" maxlength="80" class="{{ $inputClass }} font-monospace" value="{{ old('slug', $service->slug) }}" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="auto">
    </div>
</div>

@include('admin.partials._locale-tabs', ['prefix' => 'service'])
<div class="tab-content mb-4">
    @foreach(config('cms.locales') as $locale => $name)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="service-{{ $locale }}" role="tabpanel">
            @if($locale !== $default)<p class="form-text text-secondary mt-0 mb-3">Laissé vide, un champ reprend la version {{ config('cms.locales')[$default] }}.</p>@endif
            @include('admin.partials._translated-field', ['model' => $service, 'name' => 'title', 'label' => 'Titre', 'locale' => $locale, 'required' => true, 'max' => 160])
            @include('admin.partials._translated-field', ['model' => $service, 'name' => 'short_description', 'label' => 'Bénéfice pour le client', 'locale' => $locale, 'required' => true, 'type' => 'textarea', 'rows' => 2, 'max' => 500])
            @include('admin.partials._translated-field', ['model' => $service, 'name' => 'deliverables', 'label' => 'Livrables', 'locale' => $locale, 'type' => 'textarea', 'rows' => 4, 'max' => 1500, 'lines' => true, 'help' => 'Un livrable par ligne.'])
            @include('admin.partials._translated-field', ['model' => $service, 'name' => 'cta_label', 'label' => 'Libellé du bouton (vide = pas de bouton)', 'locale' => $locale, 'max' => 80])
        </div>
    @endforeach
</div>

<div class="form-check form-switch mb-4">
    <input type="hidden" name="is_published" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" @checked(old('is_published', $service->is_published))>
    <label class="form-check-label text-white small" for="is_published">En ligne (affiché par les blocs « Domaines d'expertise » réglés sur les services de l'admin)</label>
</div>
