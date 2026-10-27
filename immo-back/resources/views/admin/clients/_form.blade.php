{{-- Compte client, partagé par create et edit. Attend $client. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="name" class="form-label text-white small fw-semibold">Nom du contact <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="{{ $inputClass }}" value="{{ old('name', $client->name) }}" required maxlength="80" autocomplete="off">
    </div>
    <div class="col-md-6">
        <label for="company" class="form-label text-white small fw-semibold">Entreprise</label>
        <input type="text" name="company" id="company" class="{{ $inputClass }}" value="{{ old('company', $client->company) }}" maxlength="100" autocomplete="off">
    </div>
</div>

<div class="mb-3">
    <label for="email" class="form-label text-white small fw-semibold">Email de connexion <span class="text-danger">*</span></label>
    <input type="email" name="email" id="email" class="{{ $inputClass }}" value="{{ old('email', $client->email) }}" required maxlength="120" autocomplete="off">
</div>

<div class="mb-3">
    <label for="password" class="form-label text-white small fw-semibold">
        {{ $client->exists ? 'Nouveau mot de passe' : 'Mot de passe temporaire' }} @unless($client->exists)<span class="text-danger">*</span>@endunless
    </label>
    <input type="password" name="password" id="password" class="{{ $inputClass }}" minlength="10" maxlength="100" autocomplete="new-password" @required(! $client->exists)>
    <div class="form-text text-secondary">
        10 caractères minimum.
        @if($client->exists) Laisser vide pour ne pas le changer ; le changer déconnecte le client. @else Le client pourra le changer depuis « Mon compte ». @endif
    </div>
</div>

<div class="form-check form-switch mb-4">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}>
    <label class="form-check-label text-white small" for="is_active">Accès au portail actif</label>
</div>
