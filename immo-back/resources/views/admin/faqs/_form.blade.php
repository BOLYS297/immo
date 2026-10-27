{{-- Question fréquente bilingue, partagée par create et edit. Attend $faq, $categories. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($default = config('cms.default_locale'))

@include('admin.partials._locale-tabs', ['prefix' => 'faq'])
<div class="tab-content mb-3">
    @foreach(config('cms.locales') as $locale => $name)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="faq-{{ $locale }}" role="tabpanel">
            @if($locale !== $default)<p class="form-text text-secondary mt-0 mb-3">Laissé vide, un champ reprend la version {{ config('cms.locales')[$default] }}.</p>@endif
            @include('admin.partials._translated-field', ['model' => $faq, 'name' => 'question', 'label' => 'Question', 'locale' => $locale, 'required' => true, 'max' => 255])
            @include('admin.partials._translated-field', ['model' => $faq, 'name' => 'answer', 'label' => 'Réponse', 'locale' => $locale, 'required' => true, 'type' => 'textarea', 'rows' => 6, 'max' => 5000, 'help' => '**gras**, - liste, [lien](/contact), ligne vide = nouveau paragraphe.'])
            @include('admin.partials._translated-field', ['model' => $faq, 'name' => 'category', 'label' => 'Catégorie', 'locale' => $locale, 'max' => 80, 'help' => $locale === $default ? 'Le bloc « FAQ » peut n\'afficher qu\'une catégorie : il utilise ce nom français.' : null])
        </div>
    @endforeach
</div>
@if($categories)
    <p class="small text-secondary mb-3">Catégories existantes : {{ implode(' · ', $categories) }}</p>
@endif

<div class="row g-3 align-items-center mb-4">
    <div class="col-md-2">
        <label for="sort_order" class="form-label text-white small fw-semibold">Ordre</label>
        <input type="number" name="sort_order" id="sort_order" min="0" max="999" class="{{ $inputClass }}" value="{{ old('sort_order', $faq->sort_order) }}" required>
    </div>
    <div class="col-md-10 pt-md-4">
        <div class="form-check form-switch">
            <input type="hidden" name="is_published" value="0">
            <input class="form-check-input" type="checkbox" role="switch" name="is_published" id="is_published" value="1" @checked(old('is_published', $faq->is_published))>
            <label class="form-check-label text-white small" for="is_published">En ligne</label>
        </div>
    </div>
</div>
