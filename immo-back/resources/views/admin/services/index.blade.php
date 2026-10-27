@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Services')
@section('page_title', 'Services')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Domaines d'expertise affichés par les blocs « Domaines d'expertise » réglés sur « Services publiés » (page Solutions).</p>
        <a href="{{ route('admin.services.create') }}" class="btn btn-info"><i class="bi bi-plus-lg me-1"></i>Nouveau service</a>
    </div>

    <div class="card-admin p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Ordre</th>
                        <th scope="col">Service</th>
                        <th scope="col">Langues</th>
                        <th scope="col">État</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                        <tr class="{{ $service->is_published ? '' : 'opacity-75' }}">
                            <td class="font-monospace text-secondary">{{ $service->sort_order }}</td>
                            <td>
                                <a href="{{ route('admin.services.edit', $service) }}" class="text-white fw-semibold text-decoration-none">{{ $service->getTranslation('title', $default) }}</a>
                                <div class="small text-secondary">{{ Str::limit($service->getTranslation('short_description', $default), 90) }}</div>
                            </td>
                            <td>
                                @foreach(config('cms.locales') as $locale => $name)
                                    <span class="badge {{ filled($service->getTranslation('title', $locale, false)) ? 'bg-info bg-opacity-25 text-info' : 'bg-secondary bg-opacity-25 text-secondary text-decoration-line-through' }}" title="{{ $name }}">{{ strtoupper($locale) }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($service->is_published)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">En ligne</span>
                                @else
                                    <span class="badge bg-secondary">Hors ligne</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-info" title="Modifier" aria-label="Modifier {{ $service->getTranslation('title', $default) }}"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Supprimer ce service ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" aria-label="Supprimer {{ $service->getTranslation('title', $default) }}"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">Aucun service.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
