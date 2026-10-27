@extends('layouts.admin')

@section('title', 'Clients du portail')
@section('page_title', 'Clients du portail')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="text-white fw-bold mb-1">Espaces clients</h5>
            <p class="text-secondary small mb-0">Chaque client se connecte sur le site (page « Connexion ») et suit son projet</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="btn btn-info">
            <i class="bi bi-plus-lg me-1"></i>Nouveau client
        </a>
    </div>

    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Client</th>
                        <th>Email</th>
                        <th>Éléments</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <a href="{{ route('admin.clients.show', $client) }}" class="text-white fw-bold fs-6 text-decoration-none">{{ $client->company ?: $client->name }}</a>
                                @if($client->company)
                                    <div class="text-info" style="font-size: 0.75rem;">{{ $client->name }}</div>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $client->email }}</td>
                            <td class="text-secondary">{{ $client->portal_items_count }}</td>
                            <td>
                                @if($client->is_active)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Actif</span>
                                @else
                                    <span class="badge bg-secondary">Désactivé</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-info" title="Gérer son portail">
                                        <i class="bi bi-kanban"></i>
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary" title="Modifier le compte">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">Aucun client pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $clients->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
