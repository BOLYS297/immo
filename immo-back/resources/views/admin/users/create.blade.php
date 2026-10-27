@extends('layouts.admin')

@section('title', 'Nouvel utilisateur')
@section('page_title', 'Nouvel utilisateur')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour à l'équipe</a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4" style="max-width: 860px;">
        <form action="{{ route('admin.users.store') }}" method="POST" data-dirty-guard>
            @csrf
            @include('admin.users._form')
            <button type="submit" class="btn btn-info px-4"><i class="bi bi-person-plus me-1"></i>Créer le compte</button>
        </form>
    </div>

@endsection
