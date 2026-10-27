@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Modifier : ' . $item->getTranslation('label', $default))
@section('page_title', 'Menu « ' . $config['label'] . ' » — ' . $item->getTranslation('label', $default))

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.menus.index') }}#menu-{{ $item->menu }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux menus
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4" style="max-width: 760px;">
        <form action="{{ route('admin.menus.items.update', $item) }}" method="POST" data-menu-form data-dirty-guard>
            @csrf
            @method('PUT')
            @include('admin.menus._fields', ['prefix' => 'item', 'item' => $item])
            @if($config['depth'] >= 2 && $item->url === null && $columns->isEmpty())
                <p class="text-secondary small">Cette colonne contient des liens : elle reste une colonne.</p>
            @endif
            <button type="submit" class="btn btn-info px-4"><i class="bi bi-save me-1"></i>Enregistrer</button>
        </form>
    </div>

    @include('admin.menus._script')

@endsection
