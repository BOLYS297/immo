{{--
    Champs d'un élément du portail selon config/portal.php.
    Attend : $type, $definition, $item (PortalItem ou null pour un ajout), $prefix (identifiant HTML unique),
    $projects (projets du client : en modification, l'élément peut changer de projet).
--}}
@php($inputClass = 'form-control form-control-sm bg-dark text-light border-secondary border-opacity-25')

<div class="row g-2">
    <div class="col-12">
        <label for="{{ $prefix }}_title" class="form-label text-white small fw-semibold mb-1">Titre</label>
        <input type="text" name="title" id="{{ $prefix }}_title" class="{{ $inputClass }}" value="{{ $item?->title }}" required maxlength="120">
    </div>
    @if($item && $projects->count() > 1)
        <div class="col-md-6">
            <label for="{{ $prefix }}_project" class="form-label text-white small fw-semibold mb-1">Projet</label>
            <select name="project" id="{{ $prefix }}_project" class="form-select form-select-sm bg-dark text-light border-secondary border-opacity-25">
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" @selected($p->id === $item->client_project_id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    @foreach($definition['fields'] as $key => $field)
        @php($value = $item ? $item->field($key) : ($field['default'] ?? ''))
        <div class="{{ in_array($field['type'], ['textarea', 'file'], true) ? 'col-12' : 'col-md-6' }}">
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
                @case('file')
                    {{-- Fichier privé : visible par le client connecté et l'équipe, jamais par une adresse publique. --}}
                    <input type="file" name="data[{{ $key }}]" id="{{ $prefix }}_{{ $key }}" accept="{{ collect($field['mimes'])->map(fn ($ext) => '.'.$ext)->implode(',') }}" class="{{ $inputClass }}">
                    @if($item?->file())
                        <div class="d-flex flex-wrap align-items-center gap-3 mt-1 small">
                            <a href="{{ route('admin.portal-items.file', $item) }}" target="_blank" rel="noopener" class="text-info text-decoration-none"><i class="bi bi-paperclip"></i> {{ $item->file()['name'] }}</a>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="remove[{{ $key }}]" value="1" id="{{ $prefix }}_{{ $key }}_remove">
                                <label class="form-check-label text-secondary" for="{{ $prefix }}_{{ $key }}_remove">Retirer ce fichier</label>
                            </div>
                        </div>
                        <div class="form-text text-secondary">Un nouveau fichier remplace l'actuel.</div>
                    @endif
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
