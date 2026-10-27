@extends('layouts.admin')

@section('title', 'Modifier l\'article — ' . $post->getTranslation('title', config('cms.default_locale')))
@section('page_title', 'Modifier : ' . Str::limit($post->getTranslation('title', config('cms.default_locale')), 40))

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <a href="{{ route('admin.posts.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux articles
        </a>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.posts.preview', $post) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info" title="Version enregistrée, telle qu'elle s'affichera">
                <i class="bi bi-eye me-1"></i>Aperçu
            </a>
            @if($post->is_published)
                <a href="{{ config('services.front.url') }}/tech-insights/{{ $post->slug }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Voir sur le site
                </a>
            @endif
        </div>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-900">
        <form action="{{ route('admin.posts.update', $post) }}" method="POST" data-dirty-guard>
            @csrf
            @method('PUT')
            @include('admin.posts._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-save me-1"></i>Enregistrer les modifications
                </button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

@endsection
