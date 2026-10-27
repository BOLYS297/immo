@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Composer : ' . $page->getTranslation('title', $default))
@section('page_title', 'Composer : ' . $page->getTranslation('title', $default))

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux pages
        </a>
        @if($page->publishedRevision && $page->is_published)
            <a href="{{ config('services.front.url') }}{{ $page->slug === 'home' ? '/' : '/' . $page->slug }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Version en ligne
            </a>
        @endif
    </div>

    @include('admin.pages._errors')

    <div class="row g-4">
        {{-- Sections : ordre, visibilité, édition --}}
        <div class="col-lg-7">
            <div class="card-admin p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="text-white fw-bold mb-1">Sections de la page</h6>
                        <p class="text-secondary small mb-0">Affichées sur le site dans cet ordre, de haut en bas</p>
                    </div>
                    <span class="badge bg-secondary">{{ $page->sections->count() }}</span>
                </div>

                <ol class="list-unstyled mb-0" data-sortable="{{ route('admin.pages.sections.reorder', $page) }}" aria-label="Sections de la page">
                    @forelse($page->sections as $section)
                        @php($block = $blocks[$section->type] ?? null)
                        @php($preview = $section->contentFor($default)['title'] ?? $section->contentFor($default)['text'] ?? null)
                        <li class="d-flex align-items-center gap-3 p-3 mb-2 rounded border border-secondary border-opacity-25 {{ $section->is_visible ? '' : 'opacity-50' }}" data-sortable-item data-id="{{ $section->id }}">
                            <button type="button" class="drag-handle" data-sortable-handle title="Glisser pour déplacer" aria-label="Glisser pour déplacer (ou utilisez les flèches)"><i class="bi bi-grip-vertical fs-5"></i></button>
                            <span class="font-monospace text-secondary small" style="width: 1.5rem;">{{ $loop->iteration }}</span>
                            <i class="bi {{ $block['icon'] ?? 'bi-exclamation-triangle text-warning' }} fs-5 text-info"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-white fw-semibold small">{{ $block['label'] ?? 'Type inconnu : ' . $section->type }}</div>
                                <div class="text-secondary small text-truncate">
                                    {{ $preview ? Str::limit($preview, 80) : 'Textes par défaut du site' }}
                                    @unless($section->is_visible) · <span class="text-warning">masquée</span>@endunless
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                @foreach(['up' => 'bi-arrow-up', 'down' => 'bi-arrow-down'] as $direction => $icon)
                                    <form action="{{ route('admin.sections.move', $section) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="{{ $direction }}">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $direction === 'up' ? 'Monter' : 'Descendre' }}" @disabled($direction === 'up' ? $loop->parent->first : $loop->parent->last)>
                                            <i class="bi {{ $icon }}"></i>
                                        </button>
                                    </form>
                                @endforeach
                                <form action="{{ route('admin.sections.visibility', $section) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ $section->is_visible ? 'Masquer' : 'Afficher' }}">
                                        <i class="bi {{ $section->is_visible ? 'bi-eye' : 'bi-eye-slash' }}"></i>
                                    </button>
                                </form>
                                @if($block)
                                    <form action="{{ route('admin.sections.duplicate', $section) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Dupliquer" aria-label="Dupliquer la section {{ $loop->iteration }}"><i class="bi bi-copy"></i></button>
                                    </form>
                                    <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-sm btn-info" title="Modifier les textes">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endif
                                <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" onsubmit="return confirm('Supprimer cette section ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="text-center text-secondary py-4">Aucune section : la page est vide sur le site.</li>
                    @endforelse
                </ol>

                <form action="{{ route('admin.pages.sections.store', $page) }}" method="POST" class="d-flex gap-2 mt-4 pt-3 border-top border-secondary border-opacity-25">
                    @csrf
                    <select name="type" class="form-select bg-dark text-light border-secondary border-opacity-25" required aria-label="Type de section à ajouter">
                        <option value="" disabled selected>Ajouter une section…</option>
                        @foreach(collect($blocks)->groupBy(fn ($definition) => $definition['group'] ?? 'Autres', preserveKeys: true) as $group => $groupBlocks)
                            <optgroup label="{{ $group }}">
                                @foreach($groupBlocks as $type => $definition)
                                    <option value="{{ $type }}">{{ $definition['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-info flex-shrink-0">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </button>
                </form>
            </div>
        </div>

        {{-- Publication, historique et réglages de la page --}}
        <div class="col-lg-5">
            @include('admin.pages._publish')
            @include('admin.pages._danger')

            <div class="card-admin p-4">
                <h6 class="text-white fw-bold mb-3">Réglages de la page</h6>
                <form action="{{ route('admin.pages.update', $page) }}" method="POST" data-dirty-guard>
                    @csrf
                    @method('PUT')
                    @include('admin.pages._form')

                    <button type="submit" class="btn btn-info px-4 py-2">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('admin.media._picker')

@endsection
