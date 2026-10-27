{{--
    Un champ du schéma config/cms.php.
    Attend : $field (définition), $name (nom HTML, ex. content[fr][title]), $id, $value.
--}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

@switch($field['type'])
    @case('textarea')
        <label for="{{ $id }}" class="form-label text-white small fw-semibold">{{ $field['label'] }}</label>
        <textarea name="{{ $name }}" id="{{ $id }}" rows="3" maxlength="2000" class="{{ $inputClass }}">{{ $value }}</textarea>
        @break

    @case('richtext')
        <label for="{{ $id }}" class="form-label text-white small fw-semibold">{{ $field['label'] }}</label>
        <textarea name="{{ $name }}" id="{{ $id }}" rows="6" maxlength="5000" class="{{ $inputClass }}" aria-describedby="{{ $id }}_help">{{ $value }}</textarea>
        <div id="{{ $id }}_help" class="form-text">
            <code>## </code>intertitre · <code>- </code>liste · <code>**gras**</code> · <code>[texte du lien](/contact)</code> · ligne vide = nouveau paragraphe
        </div>
        @break

    @case('image')
        {{-- Identifiant du média ; choisi dans la fenêtre « Médiathèque » (admin.media._picker). --}}
        @php($media = ctype_digit((string) $value) ? \App\Models\Media::find($value) : null)
        <span class="form-label text-white small fw-semibold d-block" id="{{ $id }}_label">{{ $field['label'] }}</span>
        <div class="d-flex align-items-center gap-3" data-media-field>
            <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $media?->id }}" data-media-input>
            <img src="{{ $media?->url() }}" alt="" data-media-preview class="rounded-3 border {{ $media ? '' : 'd-none' }}" style="width: 120px; height: 90px; object-fit: cover; border-color: var(--line) !important;">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-info" data-media-choose aria-describedby="{{ $id }}_label"><i class="bi bi-images me-1"></i>{{ $media ? 'Changer' : 'Choisir une image' }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary {{ $media ? '' : 'd-none' }}" data-media-clear><i class="bi bi-x-lg me-1"></i>Retirer</button>
            </div>
        </div>
        @break

    @case('link')
        <label for="{{ $id }}" class="form-label text-white small fw-semibold">{{ $field['label'] }}</label>
        <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="255" class="{{ $inputClass }} font-monospace" placeholder="/contact ou https://…">
        @break

    @default
        <label for="{{ $id }}" class="form-label text-white small fw-semibold">{{ $field['label'] }}</label>
        <input type="text" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" maxlength="255" class="{{ $inputClass }}">
@endswitch
