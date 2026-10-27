@extends('layouts.admin')

@section('title', 'Message de ' . $message->name)
@section('page_title', 'Message : ' . $message->subject)

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <a href="{{ route('admin.contact.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux messages
        </a>

        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.contact.destroy', $message->id) }}" method="POST" onsubmit="return confirm('Supprimer ce message ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Supprimer
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-admin p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-15 pb-3">
                    <h4 class="text-white fw-bold mb-0">{{ $message->subject }}</h4>
                    <span class="text-secondary small font-monospace">
                        {{ $message->created_at->format('d/m/Y à H:i') }}
                    </span>
                </div>

                <div class="p-4 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-15 text-light mb-4" style="line-height: 1.8; font-size: 1.05rem;">
                    {!! nl2br(e($message->message)) !!}
                </div>

                <div class="d-flex gap-2">
                    <a href="mailto:{{ $message->email }}?subject=Re:%20{{ urlencode($message->subject) }}" class="btn btn-info">
                        <i class="bi bi-reply-fill me-1"></i>Répondre par e-mail
                    </a>
                    @if($message->phone)
                        <a href="tel:{{ $message->phone }}" class="btn btn-outline-secondary">
                            <i class="bi bi-telephone me-1"></i>Appeler le {{ $message->phone }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status Update -->
            <div class="card-admin p-4 mb-4">
                <h5 class="text-white fw-bold mb-3">Statut du message</h5>
                <form action="{{ route('admin.contact.status', $message->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select bg-dark text-light border-secondary border-opacity-25">
                            <option value="nouveau" {{ $message->status === 'nouveau' ? 'selected' : '' }}>Nouveau</option>
                            <option value="lu" {{ $message->status === 'lu' ? 'selected' : '' }}>Lu</option>
                            <option value="traite" {{ $message->status === 'traite' ? 'selected' : '' }}>Traité</option>
                            <option value="archive" {{ $message->status === 'archive' ? 'selected' : '' }}>Archivé</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info w-100">
                        Mettre à jour
                    </button>
                </form>
            </div>

            <!-- Sender info -->
            <div class="card-admin p-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    Informations Expéditeur
                </h5>
                <div class="d-flex flex-column gap-3 small">
                    <div>
                        <span class="text-secondary d-block">Nom :</span>
                        <span class="text-white fw-bold">{{ $message->name }}</span>
                    </div>
                    <div>
                        <span class="text-secondary d-block">E-mail :</span>
                        <a href="mailto:{{ $message->email }}" class="text-info text-decoration-none">
                            {{ $message->email }}
                        </a>
                    </div>
                    @if($message->phone)
                        <div>
                            <span class="text-secondary d-block">Téléphone :</span>
                            <span class="text-light">{{ $message->phone }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
