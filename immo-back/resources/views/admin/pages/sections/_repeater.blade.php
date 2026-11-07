{{--
    Liste répétable d'un champ « repeater » ; peut être imbriquée dans un élément (ex. boutons d'une carte).
    Attend : $field (le repeater), $name (nom HTML de la liste, ex. content[fr][items]), $id, $items, $depth (0 = liste de la section).
    Le marqueur d'index diffère par niveau : ajouter une carte ne touche pas au modèle de ses boutons.
--}}
@php($placeholder = $depth === 0 ? '__INDEX__' : '__SUB__')
<fieldset @class(['mb-4' => $depth === 0, 'mt-1' => $depth > 0]) data-repeater data-max="{{ $field['max'] ?? 12 }}" data-placeholder="{{ $placeholder }}">
    <legend @class(['form-label text-white small fw-semibold mb-2', 'fs-6' => $depth === 0])>{{ $field['label'] }}</legend>
    <div data-repeater-items>
        @foreach(array_values(is_array($items) ? $items : []) as $index => $item)
            @include('admin.pages.sections._repeater-item', ['index' => $index, 'item' => is_array($item) ? $item : []])
        @endforeach
    </div>
    <template data-repeater-template>
        @include('admin.pages.sections._repeater-item', ['index' => $placeholder, 'item' => []])
    </template>
    <button type="button" class="btn btn-sm btn-outline-info" data-repeater-add>
        <i class="bi bi-plus-lg me-1"></i>Ajouter : {{ Str::lower($field['item_label']) }}
    </button>
</fieldset>
