@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'FAQ')
@section('page_title', 'Questions fréquentes')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Questions affichées par le bloc « FAQ (questions gérées) », toutes ou par catégorie.</p>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-info"><i class="bi bi-plus-lg me-1"></i>Nouvelle question</a>
    </div>

    <div class="card-admin p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Ordre</th>
                        <th scope="col">Question</th>
                        <th scope="col">Catégorie</th>
                        <th scope="col">État</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faqs as $faq)
                        <tr class="{{ $faq->is_published ? '' : 'opacity-75' }}">
                            <td class="font-monospace text-secondary">{{ $faq->sort_order }}</td>
                            <td><a href="{{ route('admin.faqs.edit', $faq) }}" class="text-white fw-semibold text-decoration-none">{{ $faq->getTranslation('question', $default) }}</a></td>
                            <td class="small">{{ $faq->getTranslation('category', $default, false) ?: '—' }}</td>
                            <td>
                                @if($faq->is_published)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">En ligne</span>
                                @else
                                    <span class="badge bg-secondary">Hors ligne</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-info" title="Modifier" aria-label="Modifier la question {{ $loop->iteration }}"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" onsubmit="return confirm('Supprimer cette question ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" aria-label="Supprimer la question {{ $loop->iteration }}"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">Aucune question.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
