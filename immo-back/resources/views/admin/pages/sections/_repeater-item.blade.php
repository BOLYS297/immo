{{-- Un élément de liste répétable. Attend : $locale, $key, $field (le repeater), $index, $item. --}}
<div class="p-3 mb-2 rounded border border-secondary border-opacity-25" data-repeater-item>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-info small fw-semibold">{{ $field['item_label'] }} n°<span data-repeater-number>{{ is_int($index) ? $index + 1 : '' }}</span></span>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-up title="Monter"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-down title="Descendre"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove title="Retirer"><i class="bi bi-x-lg"></i></button>
        </div>
    </div>
    @foreach($field['fields'] as $subKey => $subField)
        <div class="{{ $loop->last ? '' : 'mb-2' }}">
            @include('admin.pages.sections._field', [
                'field' => $subField,
                'name' => "content[$locale][$key][$index][$subKey]",
                'id' => "content_{$locale}_{$key}_{$index}_{$subKey}",
                'value' => $item[$subKey] ?? '',
            ])
        </div>
    @endforeach
</div>
