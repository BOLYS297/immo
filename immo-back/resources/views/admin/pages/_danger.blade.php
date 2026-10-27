{{-- Suppression de la page depuis son écran de composition. Attend $page. Pages système : non supprimables. --}}
@can('pages.publish')
    @unless($page->isSystem())
        <div class="card-admin p-4 mb-4 border border-danger border-opacity-25">
            <h6 class="text-white fw-bold mb-1">Supprimer la page</h6>
            <p class="small text-secondary mb-3">
                Supprime la page, toutes ses sections et son historique de versions. Elle disparaît aussitôt du site
                ({{ config('services.front.url') }}/{{ $page->slug }}) : pensez à retirer les liens qui y mènent dans les menus.
            </p>
            <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Supprimer définitivement la page « {{ addslashes($page->getTranslation('title', config('cms.default_locale'))) }} », ses sections et son historique ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Supprimer cette page</button>
            </form>
        </div>
    @endunless
@endcan
