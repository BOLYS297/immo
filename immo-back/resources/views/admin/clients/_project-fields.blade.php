{{--
    Champs d'un projet client. Attend : $clientProject (ClientProject ou null pour une création), $prefix (identifiant HTML unique).
--}}
@php($inputClass = 'form-control form-control-sm bg-dark text-light border-secondary border-opacity-25')

<div class="row g-2">
    <div class="col-md-8">
        <label for="{{ $prefix }}_name" class="form-label text-white small fw-semibold mb-1">Nom du projet</label>
        <input type="text" name="name" id="{{ $prefix }}_name" class="{{ $inputClass }}" value="{{ $clientProject?->name }}" required maxlength="120" placeholder="ex. Site vitrine, Application mobile…">
    </div>
    <div class="col-md-4">
        <label for="{{ $prefix }}_status" class="form-label text-white small fw-semibold mb-1">Statut</label>
        <select name="status" id="{{ $prefix }}_status" class="form-select form-select-sm bg-dark text-light border-secondary border-opacity-25">
            @foreach(config('portal.project_statuses') as $value => $label)
                <option value="{{ $value }}" @selected(($clientProject?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label for="{{ $prefix }}_summary" class="form-label text-white small fw-semibold mb-1">Description courte (visible par le client)</label>
        <input type="text" name="summary" id="{{ $prefix }}_summary" class="{{ $inputClass }}" value="{{ $clientProject?->summary }}" maxlength="300">
    </div>
</div>
