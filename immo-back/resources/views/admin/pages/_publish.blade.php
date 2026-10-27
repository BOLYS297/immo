{{--
    Publication d'une page (workflow éditorial) : état, aperçu, publication, annulation, historique des versions.
    Attend : $page (avec publishedRevision), $revisions.
--}}
@php($changes = $page->hasUnpublishedChanges())
@php($published = $page->publishedRevision)

<div class="card-admin p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <h6 class="text-white fw-bold mb-0">Publication</h6>
        @if(! $published)
            <span class="badge bg-secondary">Jamais publiée</span>
        @elseif(! $page->is_published)
            <span class="badge bg-secondary">Hors ligne</span>
        @elseif($changes)
            <span class="badge bg-warning text-dark">Modifications non publiées</span>
        @else
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">À jour en ligne</span>
        @endif
    </div>
    <p class="small text-secondary mb-3">
        @if(! $published)
            Cette page n'est pas encore sur le site : vérifiez-la dans l'aperçu, puis publiez-la.
        @elseif($changes)
            Vos modifications (sections, titre, SEO) sont enregistrées en brouillon. Le site affiche encore la version du {{ $published->created_at->format('d/m/Y à H:i') }}.
        @else
            Le site affiche la version publiée le {{ $published->created_at->format('d/m/Y à H:i') }}@if($published->author) par {{ $published->author->name }}@endif.
        @endif
    </p>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info"><i class="bi bi-eye me-1"></i>Aperçu du brouillon</a>
        @if($changes)
            @can('pages.publish')
                <form action="{{ route('admin.pages.publish', $page) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-info"><i class="bi bi-cloud-upload me-1"></i>{{ $published ? 'Publier les modifications' : 'Publier la page' }}</button>
                </form>
            @else
                <span class="small text-secondary align-self-center"><i class="bi bi-hourglass-split me-1"></i>En attente de publication par un éditeur</span>
            @endcan
            @if($published)
                <form action="{{ route('admin.pages.discard', $page) }}" method="POST" onsubmit="return confirm('Annuler toutes les modifications non publiées et revenir à la version en ligne ?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Annuler les modifications</button>
                </form>
            @endif
        @endif
    </div>
</div>

@if($revisions->isNotEmpty())
    <div class="card-admin p-4 mb-4">
        <h6 class="text-white fw-bold mb-1">Historique des versions</h6>
        <p class="small text-secondary mb-3">Chaque publication est conservée ({{ \App\Models\Page::KEEP_REVISIONS }} dernières). Restaurer une version la reprend en brouillon, à publier ensuite.</p>
        <ol class="list-unstyled mb-0">
            @foreach($revisions as $revision)
                <li class="d-flex align-items-center gap-2 py-2 {{ $loop->last ? '' : 'border-bottom border-secondary border-opacity-25' }}">
                    <div class="flex-grow-1 min-w-0 small">
                        <div class="text-white">
                            {{ $revision->created_at->format('d/m/Y H:i') }}
                            @if($revision->id === $page->published_revision_id)<span class="badge bg-info text-dark ms-1">en ligne</span>@endif
                        </div>
                        <div class="text-secondary text-truncate">{{ $revision->author?->name ?? 'Installation' }}</div>
                    </div>
                    <a href="{{ route('admin.pages.preview', [$page, 'revision' => $revision->id]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Aperçu de cette version" aria-label="Aperçu de la version du {{ $revision->created_at->format('d/m/Y H:i') }}"><i class="bi bi-eye"></i></a>
                    <form action="{{ route('admin.pages.restore', [$page, $revision]) }}" method="POST" onsubmit="return confirm('Remplacer le brouillon actuel par cette version ?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Restaurer en brouillon" aria-label="Restaurer la version du {{ $revision->created_at->format('d/m/Y H:i') }}"><i class="bi bi-clock-history"></i></button>
                    </form>
                </li>
            @endforeach
        </ol>
    </div>
@endif
