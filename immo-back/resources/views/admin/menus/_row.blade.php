{{-- Une ligne de menu (contenu d'un <li> triable). Attend : $item, $first, $last, $config, $default ; $nested pour un lien dans une colonne. --}}
<div class="d-flex align-items-center gap-3 p-2 ps-2 mb-2 rounded border border-secondary border-opacity-25 {{ ($nested ?? false) ? 'ms-4' : '' }}">
    <button type="button" class="drag-handle" data-sortable-handle title="Glisser pour déplacer" aria-label="Glisser pour déplacer « {{ $item->getTranslation('label', $default) }} » (ou utilisez les flèches)"><i class="bi bi-grip-vertical fs-5"></i></button>
    <i class="bi {{ $item->url === null ? 'bi-layout-three-columns' : 'bi-link-45deg' }} text-info"></i>
    <div class="flex-grow-1 min-w-0">
        <div class="text-white fw-semibold small">
            {{ $item->getTranslation('label', $default) }}
            @if($item->url === null)<span class="text-secondary fw-normal"> · colonne</span>@endif
        </div>
        @if($item->url !== null)
            <div class="text-secondary small text-truncate font-monospace">
                {{ $item->url }}
                @if($item->new_tab) <i class="bi bi-box-arrow-up-right ms-1" title="Nouvel onglet"></i>@endif
            </div>
        @endif
    </div>
    @if($config['display'] && $item->display !== 'all')
        <span class="badge bg-secondary flex-shrink-0">{{ config('cms.menu_display')[$item->display] }}</span>
    @endif
    <div class="d-flex gap-1 flex-shrink-0">
        @foreach(['up' => 'bi-arrow-up', 'down' => 'bi-arrow-down'] as $direction => $icon)
            <form action="{{ route('admin.menus.items.move', $item) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="direction" value="{{ $direction }}">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $direction === 'up' ? 'Monter' : 'Descendre' }}" aria-label="{{ $direction === 'up' ? 'Monter' : 'Descendre' }} « {{ $item->getTranslation('label', $default) }} »" @disabled($direction === 'up' ? $first : $last)>
                    <i class="bi {{ $icon }}"></i>
                </button>
            </form>
        @endforeach
        <a href="{{ route('admin.menus.items.edit', $item) }}" class="btn btn-sm btn-info" title="Modifier" aria-label="Modifier « {{ $item->getTranslation('label', $default) }} »"><i class="bi bi-pencil"></i></a>
        <form action="{{ route('admin.menus.items.destroy', $item) }}" method="POST" onsubmit="return confirm('{{ $item->url === null ? 'Supprimer cette colonne et tous ses liens ?' : 'Supprimer ce lien ?' }}');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" aria-label="Supprimer « {{ $item->getTranslation('label', $default) }} »"><i class="bi bi-trash"></i></button>
        </form>
    </div>
</div>
