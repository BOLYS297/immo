{{-- Un élément de liste répétable. Attend : $field (le repeater), $name, $id (de la liste), $depth, $index, $item. --}}
<div @class(['rounded border border-secondary border-opacity-25', 'p-3 mb-2' => $depth === 0, 'p-2 mb-2 bg-black bg-opacity-25' => $depth > 0]) data-repeater-item>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-info small fw-semibold">{{ $field['item_label'] }} n°<span data-repeater-number>{{ is_int($index) ? $index + 1 : '' }}</span></span>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-up title="Monter"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-down title="Descendre"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove title="Retirer"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    {{-- Sous-liste (boutons, réseaux) : champs côte à côte. --}}
    <div @class(['row g-2' => $depth > 0])>
        @foreach($field['fields'] as $subKey => $subField)
            <div @class(['mb-2' => $depth === 0 && ! $loop->last, 'col-md-6' => $depth > 0])>
                @if($subField['type'] === 'repeater')
                    @include('admin.pages.sections._repeater', [
                        'field' => $subField,
                        'name' => "{$name}[{$index}][{$subKey}]",
                        'id' => "{$id}_{$index}_{$subKey}",
                        'items' => $item[$subKey] ?? [],
                        'depth' => $depth + 1,
                    ])
                @else
                    @include('admin.pages.sections._field', [
                        'field' => $subField,
                        'name' => "{$name}[{$index}][{$subKey}]",
                        'id' => "{$id}_{$index}_{$subKey}",
                        'value' => $item[$subKey] ?? '',
                    ])
                @endif
            </div>
        @endforeach
    </div>
</div>
