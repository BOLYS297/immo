@extends('layouts.admin')

@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

@section('title', 'Mon compte')
@section('page_title', 'Mon compte')

@section('content')

    @include('admin.pages._errors')

    <div class="card-admin p-4" style="max-width: 760px;">
        <p class="small text-secondary">Rôle : <strong class="text-white">{{ $user->roleLabel() }}</strong> — {{ config('admin.roles')[$user->staffRole()]['description'] ?? '' }}</p>

        <form action="{{ route('admin.account.update') }}" method="POST" data-dirty-guard>
            @csrf
            @method('PUT')

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label text-white small fw-semibold">Nom</label>
                    <input type="text" name="name" id="name" class="{{ $inputClass }}" value="{{ old('name', $user->name) }}" required maxlength="120" autocomplete="name">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label text-white small fw-semibold">E-mail de connexion</label>
                    <input type="email" name="email" id="email" class="{{ $inputClass }}" value="{{ old('email', $user->email) }}" required maxlength="191" autocomplete="email">
                </div>
            </div>

            <h6 class="text-white fw-bold mb-3 pt-3 border-top border-secondary border-opacity-25">Changer de mot de passe</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label text-white small fw-semibold">Nouveau mot de passe</label>
                    <input type="password" name="password" id="password" class="{{ $inputClass }}" autocomplete="new-password" minlength="12" aria-describedby="password_help">
                    <div id="password_help" class="form-text text-secondary">12 caractères minimum, avec des lettres et des chiffres. Vos autres sessions seront fermées.</div>
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label text-white small fw-semibold">Confirmation</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="{{ $inputClass }}" autocomplete="new-password">
                </div>
            </div>

            <div class="mb-4" style="max-width: 360px;">
                <label for="current_password" class="form-label text-white small fw-semibold">Mot de passe actuel</label>
                <input type="password" name="current_password" id="current_password" class="{{ $inputClass }}" autocomplete="current-password" aria-describedby="current_help">
                <div id="current_help" class="form-text text-secondary">Requis pour changer votre e-mail ou votre mot de passe.</div>
            </div>

            <button type="submit" class="btn btn-info px-4"><i class="bi bi-save me-1"></i>Enregistrer</button>
        </form>
    </div>

@endsection
