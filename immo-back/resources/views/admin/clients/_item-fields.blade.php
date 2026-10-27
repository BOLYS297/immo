{{--
    Champs d'un élément du portail selon config/portal.php.
    Attend : $type, $definition, $item (PortalItem ou null pour un ajout), $prefix (identifiant HTML unique).
--}}
@php($inputClass = 'form-control form-control-sm bg-dark text-light border-secondary border-opacity-25')

<div class="row g-2">
    <div class="col-12">
        <label for="{{ $prefix }}_title" class="form-label text-white small fw-semibold mb-1">Titre</label>
        <input type="text" name="title" id="{{ $prefix }}_title" class="{{ $inputClass }}" value="{{ $item?->title }}" required maxlength="120">
    </div>
    @foreach($definition['fields'] as $key => $field)
        @php($value = $item ? $item->field($key) : ($field['default'] ?? ''))
        <div class="{{ $field['type'] === 'textarea' ? 'col-12' : 'col-md-6' }}">
            <label for="{{ $prefix }}_{{ $key }}" class="form-label text-white small fw-semibold mb-1">{{ $field['label'] }}</label>
            @switch($field['type'])
                @case('textarea')
                    <textarea name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" rows="3" maxlength="{{ $field['max'] }}" class="{{ $inputClass }}">{{ $value }}</textarea>
                    @break
                @case('select')
                    <select name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" class="form-select form-select-sm bg-dark text-light border-secondary border-opacity-25">
                        @foreach($field['options'] as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                        @endforeach
                    </select>
                    @break
                @case('integer')
                    <input type="number" name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" value="{{ $value }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" class="{{ $inputClass }}">
                    @break
                @case('amount')
                    <input type="number" name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" value="{{ $value }}" min="0" step="1" class="{{ $inputClass }}" placeholder="Vide si sans montant">
                    @break
                @case('url')
                    <input type="url" name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" value="{{ $value }}" maxlength="{{ $field['max'] }}" class="{{ $inputClass }} font-monospace" placeholder="https://…">
                    @break
                @default
                    <input type="text" name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" value="{{ $value }}" maxlength="{{ $field['max'] }}" class="{{ $inputClass }}">
            @endswitch
        </div>
    @endforeach
</div>
