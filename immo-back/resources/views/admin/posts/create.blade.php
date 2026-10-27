@extends('layouts.admin')

@section('title', 'Nouvel article')
@section('page_title', 'Nouvel article Tech Insights')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux articles
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-900">
        <form action="{{ route('admin.posts.store') }}" method="POST" data-dirty-guard>
            @csrf
            @include('admin.posts._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-plus-lg me-1"></i>Créer l'article
                </button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

@endsection
