{{--
    Liste répétable d'un champ « repeater » ; peut être imbriquée dans un élément (ex. boutons d'une carte).
    Attend : $field (le repeater), $name (nom HTML de la liste, ex. content[fr][items]), $id, $items, $depth (0 = liste de la section),
    $sourceItems (autre langue : liste de la langue par défaut, dont chaque élément est traduit au même rang ; null sinon),
    $syncKey (liste de la section : déplacer ou retirer un élément de la langue par défaut déplace ou retire aussi ses traductions).
    Le marqueur d'index diffère par niveau : ajouter une carte ne touche pas au modèle de ses boutons.
--}}
@php($placeholder = $depth === 0 ? '__INDEX__' : '__SUB__')
@php($items = array_values(is_array($items) ? $items : []))
@php($sourceItems = $sourceItems ?? null)
@php($count = max(count($items), count($sourceItems ?? [])))
<fieldset @class(['mb-4' => $depth === 0, 'mt-1' => $depth > 0]) data-repeater data-max="{{ $field['max'] ?? 12 }}" data-placeholder="{{ $placeholder }}"
    @if($depth === 0 && isset($syncKey)) data-sync="{{ $syncKey }}" @endif @if($sourceItems !== null) data-translation @endif>
    <legend @class(['form-label text-white small fw-semibold mb-2', 'fs-6' => $depth === 0])>{{ $field['label'] }}</legend>
    <div data-repeater-items>
        @for($index = 0; $index < $count; $index++)
            @include('admin.pages.sections._repeater-item', [
                'index' => $index,
                'item' => is_array($items[$index] ?? null) ? $items[$index] : [],
                'sourceItem' => $sourceItems !== null && is_array($sourceItems[$index] ?? null) ? $sourceItems[$index] : null,
                'translating' => $sourceItems !== null,
            ])
        @endfor
    </div>
    <template data-repeater-template>
        @include('admin.pages.sections._repeater-item', ['index' => $placeholder, 'item' => [], 'sourceItem' => null, 'translating' => $sourceItems !== null])
    </template>
    <button type="button" class="btn btn-sm btn-outline-info" data-repeater-add>
        <i class="bi bi-plus-lg me-1"></i>Ajouter : {{ Str::lower($field['item_label']) }}
    </button>
</fieldset>
