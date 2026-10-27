@extends('layouts.admin')

@section('title', 'Accès refusé')
@section('page_title', 'Accès refusé')

@section('content')
    <div class="card-admin p-5 text-center mx-auto" style="max-width: 560px;">
        <i class="bi bi-shield-lock fs-1 text-info"></i>
        <h2 class="h4 text-white fw-bold mt-3">Cette rubrique n'est pas ouverte à votre rôle</h2>
        <p class="text-secondary">Vous êtes connecté en tant que <strong>{{ auth()->user()->roleLabel() }}</strong>. Demandez à un administrateur si vous avez besoin d'y accéder.</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-info mt-2"><i class="bi bi-grid me-1"></i>Tableau de bord</a>
    </div>
@endsection
