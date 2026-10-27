@extends('layouts.admin')

@section('title', 'Messages de Contact')
@section('page_title', 'Messages Reçus')

@section('content')

    <!-- Status filter pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.contact.index') }}" class="btn btn-sm {{ !$statusFilter ? 'btn-info' : 'btn-dark border-secondary border-opacity-25' }}">
            Tous <span class="badge bg-secondary ms-1">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('admin.contact.index', ['status' => 'nouveau']) }}" class="btn btn-sm {{ $statusFilter === 'nouveau' ? 'btn-danger' : 'btn-dark border-secondary border-opacity-25' }}">
            Nouveaux <span class="badge bg-danger ms-1">{{ $statusCounts['nouveau'] }}</span>
        </a>
        <a href="{{ route('admin.contact.index', ['status' => 'lu']) }}" class="btn btn-sm {{ $statusFilter === 'lu' ? 'btn-warning text-dark' : 'btn-dark border-secondary border-opacity-25' }}">
            Lus <span class="badge bg-warning text-dark ms-1">{{ $statusCounts['lu'] }}</span>
        </a>
        <a href="{{ route('admin.contact.index', ['status' => 'traite']) }}" class="btn btn-sm {{ $statusFilter === 'traite' ? 'btn-success' : 'btn-dark border-secondary border-opacity-25' }}">
            Traités <span class="badge bg-success ms-1">{{ $statusCounts['traite'] }}</span>
        </a>
        <a href="{{ route('admin.contact.index', ['status' => 'archive']) }}" class="btn btn-sm {{ $statusFilter === 'archive' ? 'btn-secondary' : 'btn-dark border-secondary border-opacity-25' }}">
            Archivés <span class="badge bg-secondary ms-1">{{ $statusCounts['archive'] }}</span>
        </a>
    </div>

    <!-- Messages table -->
    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Expéditeur</th>
                        <th>Sujet & Aperçu</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $msg)
                        <tr>
                            <td>
                                <div class="text-white fw-bold">{{ $msg->name }}</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    <i class="bi bi-envelope me-1"></i>{{ $msg->email }}
                                </div>
                                @if($msg->phone)
                                    <div class="text-secondary" style="font-size: 0.75rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $msg->phone }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.contact.show', $msg->id) }}" class="text-info fw-semibold text-decoration-none">
                                    {{ $msg->subject }}
                                </a>
                                <div class="text-secondary" style="font-size: 0.75rem;">
                                    {{ Str::limit($msg->message, 80) }}
                                </div>
                            </td>
                            <td class="font-monospace text-secondary" style="font-size: 0.75rem;">
                                {{ $msg->created_at->format('d/m/Y H:i') }}
                                <div class="text-secondary" style="font-size: 0.7rem;">{{ $msg->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($msg->status === 'nouveau')
                                    <span class="badge bg-danger">Nouveau</span>
                                @elseif($msg->status === 'lu')
                                    <span class="badge bg-warning text-dark">Lu</span>
                                @elseif($msg->status === 'traite')
                                    <span class="badge bg-success">Traité</span>
                                @else
                                    <span class="badge bg-secondary">Archivé</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.contact.show', $msg->id) }}" class="btn btn-sm btn-info" title="Consulter">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.contact.destroy', $msg->id) }}" method="POST" onsubmit="return confirm('Supprimer ce message ?');">
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
                            <td colspan="5" class="text-center text-secondary py-5">
                                Aucun message dans cette section.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $messages->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
