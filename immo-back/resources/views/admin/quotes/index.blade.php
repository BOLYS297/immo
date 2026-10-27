@extends('layouts.admin')

@section('title', 'Gestion des Demandes de Devis')
@section('page_title', 'Demandes de Devis')

@section('content')

    <!-- Status filter pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm {{ !$statusFilter ? 'btn-info' : 'btn-dark border-secondary border-opacity-25' }}">
            Tous <span class="badge bg-secondary ms-1">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'nouvelle']) }}" class="btn btn-sm {{ $statusFilter === 'nouvelle' ? 'btn-danger' : 'btn-dark border-secondary border-opacity-25' }}">
            Nouvelles <span class="badge bg-danger ms-1">{{ $statusCounts['nouvelle'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'en_cours']) }}" class="btn btn-sm {{ $statusFilter === 'en_cours' ? 'btn-warning text-dark' : 'btn-dark border-secondary border-opacity-25' }}">
            En cours <span class="badge bg-warning text-dark ms-1">{{ $statusCounts['en_cours'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'devis_envoye']) }}" class="btn btn-sm {{ $statusFilter === 'devis_envoye' ? 'btn-primary' : 'btn-dark border-secondary border-opacity-25' }}">
            Devis envoyé <span class="badge bg-primary ms-1">{{ $statusCounts['devis_envoye'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'acceptee']) }}" class="btn btn-sm {{ $statusFilter === 'acceptee' ? 'btn-success' : 'btn-dark border-secondary border-opacity-25' }}">
            Acceptées <span class="badge bg-success ms-1">{{ $statusCounts['acceptee'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'refusee']) }}" class="btn btn-sm {{ $statusFilter === 'refusee' ? 'btn-secondary' : 'btn-dark border-secondary border-opacity-25' }}">
            Refusées <span class="badge bg-secondary ms-1">{{ $statusCounts['refusee'] }}</span>
        </a>
        <a href="{{ route('admin.quotes.index', ['status' => 'cloturee']) }}" class="btn btn-sm {{ $statusFilter === 'cloturee' ? 'btn-dark' : 'btn-dark border-secondary border-opacity-25' }}">
            Clôturées <span class="badge bg-dark ms-1">{{ $statusCounts['cloturee'] }}</span>
        </a>
    </div>

    <!-- Search bar & Tools -->
    <div class="card-admin p-3 mb-4">
        <form action="{{ route('admin.quotes.index') }}" method="GET" class="row g-2 align-items-center">
            @if($statusFilter)
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary border-opacity-25 text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control bg-dark text-light border-secondary border-opacity-25" placeholder="Rechercher par référence, nom, e-mail ou société..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-info">Filtrer</button>
                @if($search || $statusFilter)
                    <a href="{{ route('admin.quotes.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Quotes Table -->
    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Référence & Date</th>
                        <th>Prospect</th>
                        <th>Services demandés</th>
                        <th>Budget & Délai</th>
                        <th>Statut</th>
                        <th>Document</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotes as $quote)
                        <tr>
                            <td>
                                <a href="{{ route('admin.quotes.show', $quote->id) }}" class="font-monospace text-info text-decoration-none fw-bold fs-6">
                                    {{ $quote->reference }}
                                </a>
                                <div class="text-secondary font-monospace" style="font-size: 0.72rem;">
                                    {{ $quote->created_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="text-white fw-bold">
                                    {{ $quote->prospect->last_name }} {{ $quote->prospect->first_name }}
                                </div>
                                @if($quote->prospect->company)
                                    <div class="text-info" style="font-size: 0.75rem;">
                                        <i class="bi bi-building me-1"></i>{{ $quote->prospect->company }}
                                    </div>
                                @endif
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    <i class="bi bi-envelope me-1"></i>{{ $quote->prospect->email }}
                                </div>
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    <i class="bi bi-telephone me-1"></i>{{ $quote->prospect->phone }}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1 max-w-250">
                                    @if($quote->domain_label)
                                        <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25" style="font-size: 0.7rem;" title="Pôle choisi sur le site">
                                            {{ $quote->domain_label }}
                                        </span>
                                    @endif
                                    @foreach($quote->services as $srv)
                                        <span class="badge bg-primary bg-opacity-25 text-info border border-primary border-opacity-25" style="font-size: 0.7rem;">
                                            {{ $srv->title }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <div><span class="text-secondary">Budget :</span> <span class="text-white">{{ $quote->budget_range ?: 'Non précisé' }}</span></div>
                                <div><span class="text-secondary">Délai :</span> <span class="text-white">{{ $quote->timeline ?: 'Non précisé' }}</span></div>
                            </td>
                            <td>
                                <span class="badge badge-status-{{ $quote->status }} px-2 py-1">
                                    {{ $quote->status_label }}
                                </span>
                            </td>
                            <td>
                                @if($quote->attachment_path)
                                    <a href="{{ route('admin.quotes.download', $quote->id) }}" class="badge bg-secondary bg-opacity-25 text-info text-decoration-none border border-secondary border-opacity-25">
                                        <i class="bi bi-paperclip me-1"></i>Fichier
                                    </a>
                                @else
                                    <span class="text-secondary" style="font-size: 0.75rem;">Aucun</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.quotes.show', $quote->id) }}" class="btn btn-sm btn-info" title="Consulter le dossier">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.quotes.destroy', $quote->id) }}" method="POST" onsubmit="return confirm('Confirmer la suppression définitive de cette demande ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Aucune demande de devis ne correspond aux critères sélectionnés.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $quotes->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
