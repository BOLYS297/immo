{{-- Avis client bilingue, partagé par create et edit. Attend $testimonial. --}}
@php($default = config('cms.default_locale'))
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

<div class="alert alert-warning border-0 small mb-4" role="note">
    <i class="bi bi-shield-exclamation me-1"></i>
    Le site affiche chaque avis avec la mention « vérifié » : ne publiez que l'avis d'un vrai client, avec son accord écrit, et uniquement des résultats prouvables.
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="client_name" class="form-label text-white small fw-semibold">Nom du client <span class="text-danger">*</span></label>
        <input type="text" name="client_name" id="client_name" class="{{ $inputClass }}" value="{{ old('client_name', $testimonial->client_name) }}" required maxlength="80">
    </div>
    <div class="col-md-6">
        <label for="pole" class="form-label text-white small fw-semibold">Pôle concerné</label>
        <input type="text" name="pole" id="pole" class="{{ $inputClass }}" value="{{ old('pole', $testimonial->pole) }}" maxlength="40" placeholder="IA & Data, Fullstack, Infra…">
    </div>
    <div class="col-md-6">
        <label for="role" class="form-label text-white small fw-semibold">Fonction</label>
        <input type="text" name="role" id="role" class="{{ $inputClass }}" value="{{ old('role', $testimonial->role) }}" maxlength="80">
    </div>
    <div class="col-md-6">
        <label for="company" class="form-label text-white small fw-semibold">Entreprise</label>
        <input type="text" name="company" id="company" class="{{ $inputClass }}" value="{{ old('company', $testimonial->company) }}" maxlength="80">
    </div>
    <div class="col-12">
        @include('admin.pages.sections._field', ['field' => ['type' => 'image', 'label' => 'Photo de la personne (carrée de préférence ; avec son accord)'], 'name' => 'photo_id', 'id' => 'photo_id', 'value' => old('photo_id', $testimonial->photo_id)])
    </div>
</div>

<ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" role="tablist">
    @foreach(config('cms.locales') as $locale => $name)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#testimonial-{{ $locale }}" type="button" role="tab">
                {{ $name }}
                @if($errors->has("*.$locale"))<i class="bi bi-exclamation-circle-fill text-danger ms-1"></i>@endif
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content mb-3">
    @foreach(config('cms.locales') as $locale => $name)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="testimonial-{{ $locale }}" role="tabpanel">
            <div class="mb-3">
                <label for="content_{{ $locale }}" class="form-label text-white small fw-semibold">Citation @if($locale === $default)<span class="text-danger">*</span>@endif</label>
                <textarea name="content[{{ $locale }}]" id="content_{{ $locale }}" rows="4" class="{{ $inputClass }}" maxlength="800" @required($locale === $default)>{{ old("content.$locale", $testimonial->getTranslation('content', $locale, false)) }}</textarea>
            </div>
            <div>
                <label for="metric_{{ $locale }}" class="form-label text-white small fw-semibold">Résultat mesuré (facultatif)</label>
                <input type="text" name="metric[{{ $locale }}]" id="metric_{{ $locale }}" class="{{ $inputClass }}" value="{{ old("metric.$locale", $testimonial->getTranslation('metric', $locale, false)) }}" maxlength="120" placeholder="ex. « 12 h de saisie économisées par semaine »">
            </div>
            @if($locale !== $default)
                <div class="form-text text-secondary mt-2">Laissé vide, un champ reprend la version {{ config('cms.locales')[$default] }}.</div>
            @endif
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="rating" class="form-label text-white small fw-semibold">Note (usage interne) <span class="text-danger">*</span></label>
        <select name="rating" id="rating" class="form-select bg-dark text-light border-secondary border-opacity-25" required>
            @for($i = 5; $i >= 1; $i--)
                <option value="{{ $i }}" @selected((int) old('rating', $testimonial->rating) === $i)>{{ $i }}/5</option>
            @endfor
        </select>
    </div>
    <div class="col-md-6">
        <label for="sort_order" class="form-label text-white small fw-semibold">Ordre d'affichage</label>
        <input type="number" name="sort_order" id="sort_order" class="{{ $inputClass }}" value="{{ old('sort_order', $testimonial->sort_order) }}" min="0">
    </div>
</div>

<div class="form-check form-switch mb-4">
    <input type="hidden" name="is_published" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" {{ old('is_published', $testimonial->is_published) ? 'checked' : '' }}>
    <label class="form-check-label text-white small" for="is_published">Publié sur la page Recommandations (accord écrit du client obtenu)</label>
</div>
