{{-- Champs d'un compte de l'équipe, partagés par create et edit. Attend $member, $roles. --}}
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')
@php($self = $member->exists && $member->is(auth()->user()))

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="name" class="form-label text-white small fw-semibold">Nom <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="{{ $inputClass }}" value="{{ old('name', $member->name) }}" required maxlength="120" autocomplete="off">
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label text-white small fw-semibold">E-mail de connexion <span class="text-danger">*</span></label>
        <input type="email" name="email" id="email" class="{{ $inputClass }}" value="{{ old('email', $member->email) }}" required maxlength="191" autocomplete="off">
    </div>
</div>

@if($self)
    {{-- Les champs d'un fieldset désactivé ne sont pas envoyés : on renvoie le rôle actuel hors du fieldset. --}}
    <input type="hidden" name="role" value="{{ $member->staffRole() }}">
@endif
<fieldset class="mb-3" @disabled($self)>
    <legend class="form-label text-white small fw-semibold">Rôle</legend>
    @if($self)
        <p class="small text-secondary">Votre propre rôle ne peut être changé que par un autre administrateur.</p>
    @endif
    <div class="row g-2">
        @foreach($roles as $key => $role)
            <div class="col-md-6">
                <label class="d-flex gap-2 p-3 rounded border border-secondary border-opacity-25 h-100" for="role_{{ $key }}">
                    <input class="form-check-input flex-shrink-0 mt-1" type="radio" name="role" id="role_{{ $key }}" value="{{ $key }}" @checked(old('role', $member->staffRole()) === $key)>
                    <span>
                        <span class="d-block fw-semibold text-white small">{{ $role['label'] }}</span>
                        <span class="d-block small text-secondary">{{ $role['description'] }}</span>
                    </span>
                </label>
            </div>
        @endforeach
    </div>
</fieldset>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label for="password" class="form-label text-white small fw-semibold">{{ $member->exists ? 'Nouveau mot de passe' : 'Mot de passe' }} @unless($member->exists)<span class="text-danger">*</span>@endunless</label>
        <input type="password" name="password" id="password" class="{{ $inputClass }}" autocomplete="new-password" minlength="12" @required(! $member->exists) aria-describedby="password_help">
        <div id="password_help" class="form-text text-secondary">12 caractères minimum, avec des lettres et des chiffres.@if($member->exists) Laisser vide pour ne pas le changer ; le changer ferme ses sessions ouvertes.@endif</div>
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label text-white small fw-semibold">Confirmation</label>
        <input type="password" name="password_confirmation" id="password_confirmation" class="{{ $inputClass }}" autocomplete="new-password">
    </div>
</div>

<div class="form-check form-switch mb-4">
    @if($self)
        <input type="hidden" name="is_active" value="1">
    @else
        <input type="hidden" name="is_active" value="0">
    @endif
    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active" value="1" @checked(old('is_active', $member->is_active)) @disabled($self)>
    <label class="form-check-label text-white small" for="is_active">Compte actif (un compte désactivé ne peut plus se connecter ; ses articles restent en ligne)</label>
</div>
