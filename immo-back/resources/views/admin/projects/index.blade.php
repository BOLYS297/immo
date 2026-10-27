@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Réalisations')
@section('page_title', 'Réalisations')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Cas clients affichés par le bloc « Réalisations ». Une réalisation n'est en ligne qu'avec l'accord écrit du client.</p>
        <a href="{{ route('admin.projects.create') }}" class="btn btn-info"><i class="bi bi-plus-lg me-1"></i>Nouvelle réalisation</a>
    </div>

    <div class="card-admin p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col"><span class="visually-hidden">Image</span></th>
                        <th scope="col">Réalisation</th>
                        <th scope="col">Client</th>
                        <th scope="col">État</th>
                        <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        <tr class="{{ $project->is_published ? '' : 'opacity-75' }}">
                            <td style="width: 88px;">
                                @if($project->image)
                                    <img src="{{ $project->image->url() }}" alt="" class="rounded-2" style="width: 72px; height: 48px; object-fit: cover;">
                                @else
                                    <span class="d-inline-grid rounded-2 border border-secondary border-opacity-25 text-secondary" style="width: 72px; height: 48px; place-items: center;"><i class="bi bi-image"></i></span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.projects.edit', $project) }}" class="text-white fw-semibold text-decoration-none">{{ $project->getTranslation('title', $default) }}</a>
                                @if($project->is_featured)<span class="badge bg-info text-dark ms-1">mise en avant</span>@endif
                                <div class="small text-secondary">{{ $project->completion_date?->format('m/Y') }}</div>
                            </td>
                            <td class="small">{{ $project->client_name ?: '—' }}</td>
                            <td>
                                @if($project->is_published && $project->client_consent_at)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25" title="Accord du client : {{ $project->client_consent_at->format('d/m/Y') }}">En ligne</span>
                                @elseif($project->is_published)
                                    <span class="badge bg-warning text-dark" title="Masquée tant que la date de l'accord du client n'est pas renseignée">Accord manquant</span>
                                @else
                                    <span class="badge bg-secondary">Hors ligne</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm btn-info" title="Modifier" aria-label="Modifier {{ $project->getTranslation('title', $default) }}"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Supprimer cette réalisation ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" aria-label="Supprimer {{ $project->getTranslation('title', $default) }}"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">Aucune réalisation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection
