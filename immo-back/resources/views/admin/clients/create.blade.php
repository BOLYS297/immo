@extends('layouts.admin')

@section('title', 'Nouveau client')
@section('page_title', 'Nouveau client du portail')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux clients
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-800">
        <form action="{{ route('admin.clients.store') }}" method="POST">
            @csrf
            @include('admin.clients._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-person-plus me-1"></i>Créer le client
                </button>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

@endsection
