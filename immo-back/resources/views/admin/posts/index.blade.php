@extends('layouts.admin')

@section('title', 'Gestion des Articles de Blog')
@section('page_title', 'Blog & Articles')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="text-white fw-bold mb-1">Articles & Veille Technologique</h5>
            <p class="text-secondary small mb-0">Partagez votre expertise et améliorez le référencement naturel (SEO)</p>
        </div>
        <a href="{{ route('admin.posts.create') }}" class="btn btn-info">
            <i class="bi bi-pencil-square me-1"></i>Rédiger un Article
        </a>
    </div>

    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Titre de l'article</th>
                        <th>Auteur</th>
                        <th>Date de publication</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                        <tr>
                            <td>
                                <div class="text-white fw-bold fs-6">{{ $post->getTranslation('title', config('cms.default_locale')) }}</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    {{ Str::limit($post->getTranslation('summary', config('cms.default_locale')), 80) }}
                                </div>
                                <div class="d-flex gap-1 mt-1">
                                    @foreach(array_keys(config('cms.locales')) as $locale)
                                        <span class="badge {{ filled($post->getTranslation('content', $locale, false)) ? 'bg-info bg-opacity-25 text-info' : 'bg-secondary bg-opacity-25 text-secondary text-decoration-line-through' }}" style="font-size: 0.65rem;" title="{{ filled($post->getTranslation('content', $locale, false)) ? 'Version rédigée' : 'Version absente' }}">{{ strtoupper($locale) }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="text-light">{{ $post->author->name ?? 'Admin' }}</span>
                            </td>
                            <td class="font-monospace text-secondary">
                                {{ $post->published_at ? $post->published_at->format('d/m/Y') : 'Non planifiée' }}
                            </td>
                            <td>
                                @if($post->is_published)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">En ligne</span>
                                @else
                                    <span class="badge bg-secondary">Brouillon</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    @if($post->is_published)
                                        <a href="{{ config('services.front.url') }}/tech-insights/{{ $post->slug }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir l'article">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endif
                                    @if(! $post->is_published)
                                        <a href="{{ route('admin.posts.preview', $post) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Aperçu du brouillon">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    @can('posts.edit', $post)
                                        <a href="{{ route('admin.posts.edit', $post->id) }}" class="btn btn-sm btn-info" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.posts.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Supprimer cet article ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-25 text-secondary align-self-center" title="Un article publié n'est modifiable que par un éditeur">Publié — lecture seule</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">
                                Aucun article rédigé pour l'instant.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $posts->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
