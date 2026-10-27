@extends('layouts.admin')

@section('title', 'Nouvelle page')
@section('page_title', 'Nouvelle page')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux pages
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-800">
        <form action="{{ route('admin.pages.store') }}" method="POST" data-dirty-guard>
            @csrf
            @include('admin.pages._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-plus-lg me-1"></i>Créer la page
                </button>
                <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

    @include('admin.media._picker')

@endsection
