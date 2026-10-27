@extends('layouts.admin')

@section('title', 'Modifier : ' . $client->name)
@section('page_title', 'Compte de ' . ($client->company ?: $client->name))

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour au portail du client
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-800 mb-4">
        <form action="{{ route('admin.clients.update', $client) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.clients._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-save me-1"></i>Enregistrer
                </button>
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

    <div class="card-admin p-4 max-w-800 border border-danger border-opacity-25">
        <h6 class="text-danger fw-bold mb-2">Supprimer le client</h6>
        <p class="text-secondary small mb-3">Supprime le compte et tous les éléments de son portail (tâches, livrables, documents, tickets). Pour suspendre l'accès sans rien perdre, désactivez plutôt le compte.</p>
        <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Supprimer définitivement ce client et tout son portail ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Supprimer définitivement</button>
        </form>
    </div>

@endsection
