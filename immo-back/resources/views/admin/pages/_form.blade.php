{{-- Champs d'une page, partagés par create et edit. Attend $page. --}}
@php($default = config('cms.default_locale'))
@php($system = $page->exists && $page->isSystem())

<div class="mb-3">
    <label for="slug" class="form-label text-white small fw-semibold">Slug (adresse de la page) <span class="text-danger">*</span></label>
    <input type="text" name="slug" id="slug" class="form-control bg-dark text-light border-secondary border-opacity-25 font-monospace" value="{{ old('slug', $page->slug) }}" required maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" @readonly($system)>
    <div class="form-text text-secondary">
        @if($system)
            Page du site, liée depuis les menus : son adresse ne change pas et elle reste publiée.
        @else
            Lettres minuscules, chiffres et tirets. La page d'accueil du site utilise le slug <code>home</code>.
        @endif
    </div>
</div>

<ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" role="tablist">
    @foreach(config('cms.locales') as $locale => $name)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#page-{{ $locale }}" type="button" role="tab">{{ $name }}</button>
        </li>
    @endforeach
</ul>

<div class="tab-content mb-3">
    @foreach(config('cms.locales') as $locale => $name)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="page-{{ $locale }}" role="tabpanel">
            <div class="mb-3">
                <label for="title_{{ $locale }}" class="form-label text-white small fw-semibold">Titre (admin et onglet du navigateur) @if($locale === $default)<span class="text-danger">*</span>@endif</label>
                <input type="text" name="title[{{ $locale }}]" id="title_{{ $locale }}" class="form-control bg-dark text-light border-secondary border-opacity-25" value="{{ old("title.$locale", $page->getTranslation('title', $locale, false)) }}" maxlength="255" @required($locale === $default)>
            </div>
            <div class="mb-3">
                <label for="meta_title_{{ $locale }}" class="form-label text-white small fw-semibold">Titre SEO</label>
                <input type="text" name="meta_title[{{ $locale }}]" id="meta_title_{{ $locale }}" class="form-control bg-dark text-light border-secondary border-opacity-25" value="{{ old("meta_title.$locale", $page->getTranslation('meta_title', $locale, false)) }}" maxlength="255" data-recommended="60">
            </div>
            <div>
                <label for="meta_description_{{ $locale }}" class="form-label text-white small fw-semibold">Description SEO</label>
                <textarea name="meta_description[{{ $locale }}]" id="meta_description_{{ $locale }}" rows="2" class="form-control bg-dark text-light border-secondary border-opacity-25" maxlength="500" data-recommended="160">{{ old("meta_description.$locale", $page->getTranslation('meta_description', $locale, false)) }}</textarea>
            </div>
            @if($locale !== $default)
                <div class="form-text text-secondary mt-2">Laissé vide, un champ reprend la version {{ config('cms.locales')[$default] }}.</div>
            @endif
        </div>
    @endforeach
</div>

{{-- SEO commun aux langues : partage sur les réseaux, indexation --}}
<div class="mb-3">
    @include('admin.pages.sections._field', ['field' => ['type' => 'image', 'label' => 'Image de partage (réseaux sociaux, 1200 × 630 px conseillés)'], 'name' => 'og_image_id', 'id' => 'og_image_id', 'value' => old('og_image_id', $page->og_image_id)])
    <div class="form-text text-secondary">Vide : image par défaut du site.</div>
</div>
<div class="form-check form-switch mb-3">
    <input type="hidden" name="noindex" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="noindex" id="noindex" value="1" @checked(old('noindex', $page->noindex))>
    <label class="form-check-label text-white small" for="noindex">Ne pas indexer (page exclue de Google et du plan du site)</label>
</div>

<div class="form-check form-switch mb-4 {{ $system || ! auth()->user()->can('pages.publish') ? 'd-none' : '' }}">
    <input type="hidden" name="is_published" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }}>
    <label class="form-check-label text-white small" for="is_published">En ligne (décochez pour retirer la page du site sans la supprimer)</label>
</div>
