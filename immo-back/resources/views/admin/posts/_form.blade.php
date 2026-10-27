{{-- Article bilingue, partagé par create et edit. Attend $post. --}}
@php($default = config('cms.default_locale'))
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="slug" class="form-label text-white small fw-semibold">Adresse (slug)</label>
        <input type="text" name="slug" id="slug" class="{{ $inputClass }} font-monospace" value="{{ old('slug', $post->slug) }}" maxlength="80" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="générée depuis le titre FR">
        <div class="form-text text-secondary">URL du site : /tech-insights/<span class="text-info">slug</span>. La changer casse les liens existants.</div>
    </div>
    <div class="col-md-3">
        <label for="published_at" class="form-label text-white small fw-semibold">Date <span class="text-danger">*</span></label>
        <input type="date" name="published_at" id="published_at" class="{{ $inputClass }}" value="{{ old('published_at', $post->published_at?->toDateString()) }}" required>
    </div>
    <div class="col-md-3">
        <label for="reading_minutes" class="form-label text-white small fw-semibold">Minutes de lecture <span class="text-danger">*</span></label>
        <input type="number" name="reading_minutes" id="reading_minutes" class="{{ $inputClass }}" value="{{ old('reading_minutes', $post->reading_minutes) }}" min="1" max="60" required>
    </div>
</div>

<ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" role="tablist">
    @foreach(config('cms.locales') as $locale => $name)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#post-{{ $locale }}" type="button" role="tab">
                {{ $name }}
                @if($errors->has("*.$locale"))<i class="bi bi-exclamation-circle-fill text-danger ms-1"></i>@endif
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content mb-4">
    @foreach(config('cms.locales') as $locale => $name)
        @php($req = $locale === $default)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="post-{{ $locale }}" role="tabpanel">
            @unless($req)
                <p class="form-text text-secondary mt-0 mb-3">Sans titre ou sans texte en {{ $name }}, l'article s'affiche entièrement en {{ config('cms.locales')[$default] }}.</p>
            @endunless
            <div class="mb-3">
                <label for="title_{{ $locale }}" class="form-label text-white small fw-semibold">Titre @if($req)<span class="text-danger">*</span>@endif</label>
                <input type="text" name="title[{{ $locale }}]" id="title_{{ $locale }}" class="{{ $inputClass }}" value="{{ old("title.$locale", $post->getTranslation('title', $locale, false)) }}" maxlength="200" @required($req)>
            </div>
            <div class="mb-3">
                <label for="summary_{{ $locale }}" class="form-label text-white small fw-semibold">Résumé @if($req)<span class="text-danger">*</span>@endif</label>
                <textarea name="summary[{{ $locale }}]" id="summary_{{ $locale }}" rows="2" class="{{ $inputClass }}" maxlength="400" @required($req)>{{ old("summary.$locale", $post->getTranslation('summary', $locale, false)) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="tags_{{ $locale }}" class="form-label text-white small fw-semibold">Thèmes</label>
                <input type="text" name="tags[{{ $locale }}]" id="tags_{{ $locale }}" class="{{ $inputClass }}" value="{{ old("tags.$locale", implode(', ', $post->getTranslation('tags', $locale, false) ?: [])) }}" maxlength="300" placeholder="IA & Automatisation, n8n">
                <div class="form-text text-secondary">Séparés par des virgules, 6 au maximum.</div>
            </div>
            <div class="mb-3">
                <label for="content_{{ $locale }}" class="form-label text-white small fw-semibold">Texte @if($req)<span class="text-danger">*</span>@endif</label>
                <textarea name="content[{{ $locale }}]" id="content_{{ $locale }}" rows="16" class="{{ $inputClass }} font-monospace small" maxlength="20000" @required($req)>{{ old("content.$locale", $post->getTranslation('content', $locale, false)) }}</textarea>
                <div class="form-text text-secondary">Intertitre : ligne commençant par « ## ». Liste : lignes commençant par « - ». Paragraphes séparés par une ligne vide. <code>**gras**</code> · <code>[texte du lien](/contact)</code></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="meta_title_{{ $locale }}" class="form-label text-white small fw-semibold">Titre SEO</label>
                    <input type="text" name="meta_title[{{ $locale }}]" id="meta_title_{{ $locale }}" class="{{ $inputClass }}" value="{{ old("meta_title.$locale", $post->getTranslation('meta_title', $locale, false)) }}" maxlength="255" placeholder="Par défaut : le titre" data-recommended="60">
                </div>
                <div class="col-md-6">
                    <label for="meta_description_{{ $locale }}" class="form-label text-white small fw-semibold">Description SEO</label>
                    <input type="text" name="meta_description[{{ $locale }}]" id="meta_description_{{ $locale }}" class="{{ $inputClass }}" value="{{ old("meta_description.$locale", $post->getTranslation('meta_description', $locale, false)) }}" maxlength="500" placeholder="Par défaut : le résumé" data-recommended="160">
                </div>
            </div>
        </div>
    @endforeach
</div>

@can('posts.publish')
    <div class="form-check form-switch mb-4">
        <input type="hidden" name="is_published" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
        <label class="form-check-label text-white small" for="is_published">Publié sur le site (à partir de la date indiquée)</label>
    </div>
@else
    <p class="small text-secondary mb-4"><i class="bi bi-info-circle me-1"></i>Brouillon : un éditeur relira l'article et le publiera.</p>
@endcan
