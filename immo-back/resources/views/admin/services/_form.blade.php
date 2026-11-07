{{-- Service bilingue, partagé par create et edit. Attend $service. La page inclut admin.media._picker. Chaque service est un pôle. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($default = config('cms.default_locale'))

{{-- Icône : nom d'une icône Lucide (bibliothèque du site) ou image importée, prioritaire. --}}
@php($iconName = old('icon', $service->icon && ! str_starts_with($service->icon, 'bi-') ? $service->icon : 'workflow'))
@php($frontUrl = rtrim((string) config('services.front.url'), '/'))
<fieldset class="mb-4 p-3 rounded border border-secondary border-opacity-25">
    <legend class="form-label text-white small fw-semibold float-none w-auto px-1 mb-0">Icône du pôle</legend>
    <div class="row g-3 align-items-start">
        <div class="col-md-6">
            <label for="icon" class="form-label text-white small fw-semibold">Nom de l'icône</label>
            <div class="d-flex align-items-center gap-3">
                <input type="text" name="icon" id="icon" list="icon-presets" class="{{ $inputClass }} font-monospace" value="{{ $iconName }}" required maxlength="60"
                    pattern="[a-z0-9]+(-[a-z0-9]+)*" aria-describedby="icon_help" data-icon-input data-icon-base="{{ $frontUrl }}/api/icons/">
                <img src="{{ $frontUrl }}/api/icons/{{ $iconName }}" alt="" width="40" height="40" class="rounded bg-light p-1 flex-shrink-0" data-icon-preview>
            </div>
            <datalist id="icon-presets">
                @foreach(config('cms.service_icons') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </datalist>
            <div id="icon_help" class="form-text text-secondary">
                Une suggestion de la liste ou n'importe quelle icône de <a href="https://lucide.dev/icons/" target="_blank" rel="noopener" class="text-info">lucide.dev/icons</a> (nom en minuscules, ex. « brain-circuit »). L'aperçu se met à jour pendant la saisie.
            </div>
        </div>
        <div class="col-md-6">
            @include('admin.pages.sections._field', ['field' => ['type' => 'image', 'label' => 'Ou icône importée (PNG, WebP ; remplace le nom ci-contre)'], 'name' => 'icon_media_id', 'id' => 'icon_media_id', 'value' => old('icon_media_id', $service->icon_media_id)])
        </div>
    </div>
</fieldset>

<div class="row g-3 mb-4">
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
