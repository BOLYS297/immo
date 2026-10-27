{{--
    Champs d'un élément de menu, partagés par l'ajout et la modification.
    Attend : $prefix (préfixe d'id unique), $item (MenuItem ou null), $config, $columns, $locales ;
    $useOld = false pour ne pas reprendre la saisie d'un autre formulaire de la page.
--}}
@php($default = config('cms.default_locale'))
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($old = fn (string $key, $value) => ($useOld ?? true) ? old($key, $value) : $value)

<div class="row g-2 mb-3">
    @foreach($locales as $locale => $name)
        <div class="col-md-6">
            <label for="{{ $prefix }}_label_{{ $locale }}" class="form-label text-white small fw-semibold">Libellé ({{ strtoupper($locale) }}) @if($locale === $default)<span class="text-danger">*</span>@endif</label>
            <input type="text" name="label[{{ $locale }}]" id="{{ $prefix }}_label_{{ $locale }}" maxlength="80" class="{{ $inputClass }}"
                value="{{ $old("label.$locale", $item?->getTranslation('label', $locale, false)) }}" @required($locale === $default)>
        </div>
    @endforeach
</div>

@if($config['depth'] >= 2 && ($item === null || $item->url !== null || $columns->isNotEmpty()))
    <div class="mb-3">
        <label for="{{ $prefix }}_parent" class="form-label text-white small fw-semibold">Colonne</label>
        <select name="parent_id" id="{{ $prefix }}_parent" class="form-select bg-dark text-light border-secondary border-opacity-25" data-menu-parent>
            <option value="">— Nouvelle colonne (titre sans lien) —</option>
            @foreach($columns as $column)
                <option value="{{ $column->id }}" @selected((string) $old('parent_id', $item?->parent_id ?? ($loop->first && ! $item ? $column->id : null)) === (string) $column->id)>Lien dans « {{ $column->getTranslation('label', $default) }} »</option>
            @endforeach
        </select>
    </div>
@elseif($config['depth'] >= 2 && $item?->url === null)
    {{-- Colonne qui contient des liens : pas de choix, mais les champs « lien » restent masqués. --}}
    <input type="hidden" value="" data-menu-parent>
@endif

<div class="mb-3" data-menu-link>
    <label for="{{ $prefix }}_url" class="form-label text-white small fw-semibold">Lien</label>
    <input type="text" name="url" id="{{ $prefix }}_url" maxlength="255" class="{{ $inputClass }} font-monospace" placeholder="/contact ou https://…" value="{{ $old('url', $item?->url) }}">
    <div class="form-text text-secondary">Page du site (<code>/a-propos</code>, <code>/services?domaine=ia#chiffrage</code>) ou adresse externe <code>https://…</code></div>
</div>

<div class="d-flex flex-wrap align-items-center gap-4 mb-4">
    @if($config['display'])
        <div>
            <label for="{{ $prefix }}_display" class="form-label text-white small fw-semibold">Affichage</label>
            <select name="display" id="{{ $prefix }}_display" class="form-select form-select-sm bg-dark text-light border-secondary border-opacity-25">
                @foreach(config('cms.menu_display') as $value => $label)
                    <option value="{{ $value }}" @selected($old('display', $item?->display ?? 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="form-check form-switch mt-md-4" data-menu-link>
        <input type="hidden" name="new_tab" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="new_tab" id="{{ $prefix }}_new_tab" value="1" @checked($old('new_tab', $item?->new_tab))>
        <label class="form-check-label text-white small" for="{{ $prefix }}_new_tab">Ouvrir dans un nouvel onglet</label>
    </div>
</div>
