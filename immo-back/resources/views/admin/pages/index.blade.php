@extends('layouts.admin')

@section('title', 'Pages & Sections')
@section('page_title', 'Pages & Sections')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="text-white fw-bold mb-1">Pages du site</h5>
            <p class="text-secondary small mb-0">Choisissez les sections affichées sur chaque page du site et leur ordre</p>
        </div>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-info">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle page
        </a>
    </div>

    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Page</th>
                        <th>Slug</th>
                        <th>Sections</th>
                        <th>Statut</th>
                        <th>Modifiée</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-white fw-bold fs-6 text-decoration-none">{{ $page->getTranslation('title', config('cms.default_locale')) }}</a>
                            </td>
                            <td class="font-monospace text-info">{{ $page->slug }} @if($page->isSystem())<span class="badge bg-secondary ms-1 font-sans" title="Page du site : adresse et publication fixes">système</span>@endif</td>
                            <td class="text-secondary">{{ $page->sections_count }}</td>
                            <td>
                                @if(! $page->publishedRevision)
                                    <span class="badge bg-secondary">Jamais publiée</span>
                                @elseif(! $page->is_published)
                                    <span class="badge bg-secondary">Hors ligne</span>
                                @elseif($page->hasUnpublishedChanges())
                                    <span class="badge bg-warning text-dark" title="Des modifications attendent d'être publiées">En ligne · modifiée</span>
                                @else
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">En ligne</span>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-info" title="Composer la page">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    {{-- Supprimer retire la page du site : réservé au droit de publication. --}}
                                    @can('pages.publish')
                                        @if($page->isSystem())
                                            <span class="btn btn-sm btn-outline-secondary disabled" title="Page du site : non supprimable"><i class="bi bi-lock"></i></span>
                                        @else
                                            <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Supprimer cette page et toutes ses sections ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                Aucune page. Lancez <code>php artisan db:seed --class=PageSeeder</code> pour créer la page d'accueil.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $pages->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
