@extends('layouts.admin')

@section('title', 'Modifier : ' . Str::limit($faq->getTranslation('question', config('cms.default_locale')), 50))
@section('page_title', 'Modifier : ' . Str::limit($faq->getTranslation('question', config('cms.default_locale')), 50))

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour à la FAQ</a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-900">
        <form action="{{ route('admin.faqs.update', $faq) }}" method="POST" data-dirty-guard>
            @csrf
            @method('PUT')
            @include('admin.faqs._form')
            <button type="submit" class="btn btn-info px-4 py-2"><i class="bi bi-save me-1"></i>Enregistrer</button>
        </form>
    </div>

@endsection
