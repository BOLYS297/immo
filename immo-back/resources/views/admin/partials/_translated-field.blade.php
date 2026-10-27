{{--
    Un champ traduit d'un formulaire (onglet d'une langue).
    Attend : $model, $name, $label, $locale ; options : $type (text|textarea, défaut text), $rows, $max, $required (langue par défaut),
    $lines (valeur = liste, une par ligne), $help, $recommended (compteur de caractères).
--}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($isDefault = $locale === config('cms.default_locale'))
@php($saved = $model->getTranslation($name, $locale, false))
@php($value = old("$name.$locale", ($lines ?? false) ? implode("\n", (array) ($saved ?: [])) : $saved))
@php($id = "{$name}_{$locale}")

<div class="mb-3">
    <label for="{{ $id }}" class="form-label text-white small fw-semibold">{{ $label }} @if(($required ?? false) && $isDefault)<span class="text-danger">*</span>@endif</label>
    @if(($type ?? 'text') === 'textarea')
        <textarea name="{{ $name }}[{{ $locale }}]" id="{{ $id }}" rows="{{ $rows ?? 3 }}" maxlength="{{ $max ?? 2000 }}" class="{{ $inputClass }}" @required(($required ?? false) && $isDefault) @if(isset($help)) aria-describedby="{{ $id }}_help" @endif @if(isset($recommended)) data-recommended="{{ $recommended }}" @endif>{{ $value }}</textarea>
    @else
        <input type="text" name="{{ $name }}[{{ $locale }}]" id="{{ $id }}" value="{{ $value }}" maxlength="{{ $max ?? 255 }}" class="{{ $inputClass }}" @required(($required ?? false) && $isDefault) @if(isset($help)) aria-describedby="{{ $id }}_help" @endif @if(isset($recommended)) data-recommended="{{ $recommended }}" @endif>
    @endif
    @isset($help)<div id="{{ $id }}_help" class="form-text text-secondary">{{ $help }}</div>@endisset
</div>
