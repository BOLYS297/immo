@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Menus du site')
@section('page_title', 'Menus du site')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Liens de l'en-tête et du pied de page du site public, dans l'ordre d'affichage.</p>
        @can('settings.manage')
            <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-info"><i class="bi bi-sliders me-1"></i>Réglages du site</a>
        @endcan
    </div>

    @include('admin.pages._errors')

    <div class="row g-4">
        @foreach($menus as $key => $menu)
            <div class="col-xl-6">
                <section id="menu-{{ $key }}" class="card-admin p-4 h-100" aria-labelledby="menu-{{ $key }}-title">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 id="menu-{{ $key }}-title" class="text-white fw-bold mb-0">{{ $menu['label'] }}</h5>
                        <span class="badge bg-secondary">{{ $menu['items']->count() }}</span>
                    </div>
                    <p class="text-secondary small mb-3">{{ $menu['help'] }}</p>

                    {{-- Glisser-déposer par niveau : les colonnes entre elles, les liens dans leur colonne. --}}
                    <ol class="list-unstyled mb-0" data-sortable="{{ route('admin.menus.reorder') }}" aria-label="{{ $menu['label'] }}">
                        @forelse($menu['items'] as $item)
                            <li data-sortable-item data-id="{{ $item->id }}">
                                @include('admin.menus._row', ['item' => $item, 'first' => $loop->first, 'last' => $loop->last, 'config' => $menu])
                                @if($menu['depth'] >= 2)
                                    <ol class="list-unstyled mb-0" data-sortable="{{ route('admin.menus.reorder') }}" aria-label="Liens de la colonne {{ $item->getTranslation('label', $default) }}">
                                        @forelse($item->children as $child)
                                            <li data-sortable-item data-id="{{ $child->id }}">
                                                @include('admin.menus._row', ['item' => $child, 'first' => $loop->first, 'last' => $loop->last, 'config' => $menu, 'nested' => true])
                                            </li>
                                        @empty
                                            <li class="ms-4 mb-2 small text-secondary">Colonne vide : elle n'est pas affichée sur le site.</li>
                                        @endforelse
                                    </ol>
                                @endif
                            </li>
                        @empty
                            <li class="text-center text-secondary py-3">Aucun lien : ce menu n'est pas affiché sur le site.</li>
                        @endforelse
                    </ol>

                    <details class="mt-3 pt-3 border-top border-secondary border-opacity-25" @if($errors->any() && old('_menu') === $key) open @endif>
                        <summary class="btn btn-sm btn-info"><i class="bi bi-plus-lg me-1"></i>Ajouter {{ $menu['depth'] >= 2 ? 'un lien ou une colonne' : 'un lien' }}</summary>
                        <form action="{{ route('admin.menus.items.store', $key) }}" method="POST" class="mt-3" data-menu-form>
                            @csrf
                            <input type="hidden" name="_menu" value="{{ $key }}">
                            @include('admin.menus._fields', [
                                'prefix' => "new_{$key}",
                                'item' => null,
                                'config' => $menu,
                                'columns' => $menu['depth'] >= 2 ? $menu['items'] : collect(),
                                'useOld' => old('_menu') === $key,
                            ])
                            <button type="submit" class="btn btn-info px-4"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                        </form>
                    </details>
                </section>
            </div>
        @endforeach
    </div>

    @include('admin.menus._script')

@endsection
