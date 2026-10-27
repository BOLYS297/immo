@extends('layouts.admin')

@section('title', 'Détail Devis ' . $quote->reference)
@section('page_title', 'Dossier Devis — ' . $quote->reference)

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
            <span class="badge badge-status-{{ $quote->status }} fs-6 px-3 py-2">
                {{ $quote->status_label }}
            </span>
            <span class="text-secondary small font-monospace">
                Reçu le {{ $quote->created_at->format('d/m/Y à H:i') }} ({{ $quote->created_at->diffForHumans() }})
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.quotes.destroy', $quote->id) }}" method="POST" onsubmit="return confirm('Attention : cette action supprimera définitivement cette demande et son éventuelle pièce jointe.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Supprimer ce dossier
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main details column -->
        <div class="col-lg-8">
            <!-- Project description & Needs -->
            <div class="card-admin p-4 mb-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    <i class="bi bi-card-text text-info me-2"></i>Description & Expression du besoin
                </h5>
                <div class=" text-primary p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-15 text-light mb-4" style="line-height: 1.8;">
                    {!! nl2br(e($quote->description)) !!}
                </div>

                <div class="row g-3 small">
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-30 border border-secondary border-opacity-10">
                            <span class="text-secondary d-block">Budget envisagé :</span>
                            <span class="text-white fw-bold fs-6">{{ $quote->budget_range ?: 'Non précisé' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded-3 bg-dark bg-opacity-30 border border-secondary border-opacity-10">
                            <span class="text-secondary d-block">Calendrier / Délai visé :</span>
                            <span class="text-white fw-bold fs-6">{{ $quote->timeline ?: 'Non précisé' }}</span>
                        </div>
                    </div>
                    @if($quote->domain_label)
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-30 border border-secondary border-opacity-10">
                                <span class="text-secondary d-block">Pôle choisi sur le site :</span>
                                <span class="text-white fw-bold fs-6">{{ $quote->domain_label }}</span>
                            </div>
                        </div>
                    @endif
                    @if($quote->locale)
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-dark bg-opacity-30 border border-secondary border-opacity-10">
                                <span class="text-secondary d-block">Langue du prospect :</span>
                                <span class="text-white fw-bold fs-6">{{ config('cms.locales')[$quote->locale] ?? $quote->locale }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Selected Services -->
            <div class="card-admin p-4 mb-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    <i class="bi bi-layers text-primary me-2"></i>Services sollicités ({{ $quote->services->count() }})
                </h5>
                <div class="d-flex flex-column gap-3">
                    @forelse($quote->services as $service)
                        <div class="p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-15 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 p-2 bg-primary bg-opacity-20 text-info">
                                    <i class="bi {{ $service->icon }} fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="text-white fw-bold mb-0">{{ $service->title }}</h6>
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary" style="font-size: 0.72rem;">
                                        {{ $service->category->name ?? 'Service' }}
                                    </span>
                                </div>
                            </div>
                            @if($service->indicative_price)
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                    {{ $service->indicative_price }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="text-secondary small">Aucun service associé.</div>
                    @endforelse
                </div>
            </div>

            <!-- Attachment Card -->
            <div class="card-admin p-4 mb-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    <i class="bi bi-paperclip text-warning me-2"></i>Pièce jointe du prospect
                </h5>
                @if($quote->attachment_path)
                    <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-dark bg-opacity-50 border border-secondary border-opacity-15">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-arrow-down fs-3 text-warning"></i>
                            <div>
                                <div class="text-white fw-semibold small">{{ $quote->attachment_original_name ?? basename($quote->attachment_path) }}</div>
                                <span class="text-secondary" style="font-size: 0.75rem;">Fichier stocké hors répertoire public (sécurisé)</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.quotes.download', $quote->id) }}" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-download me-1"></i>Télécharger le fichier
                        </a>
                    </div>
                @else
                    <div class="text-secondary small">
                        Aucun document joint pour cette demande de devis.
                    </div>
                @endif
            </div>

            <!-- Internal Team Notes -->
            <div class="card-admin p-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    <i class="bi bi-journal-check text-success me-2"></i>Notes internes & Suivi de négociation
                </h5>
                <form action="{{ route('admin.quotes.notes', $quote->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <textarea name="internal_notes" rows="4" class="form-control bg-dark text-light border-secondary border-opacity-25" placeholder="Ajoutez vos remarques internes sur l'échange, les estimations de charge, ou l'avancement commercial...">{{ old('internal_notes', $quote->internal_notes) }}</textarea>
                        <div class="form-text text-secondary small">Ces notes sont strictement confidentielles et réservées à l'équipe interne.</div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-save me-1"></i>Enregistrer les notes
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar column: Prospect & Status actions -->
        <div class="col-lg-4">
            <!-- Status Update Box -->
            <div class="card-admin p-4 mb-4 border-cyan-glow">
                <h5 class="text-white fw-bold mb-3">Statut du dossier</h5>
                <form action="{{ route('admin.quotes.status', $quote->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <select name="status" class="form-select bg-dark text-light border-secondary border-opacity-25">
                            <option value="nouvelle" {{ $quote->status === 'nouvelle' ? 'selected' : '' }}>Nouvelle (à traiter)</option>
                            <option value="en_cours" {{ $quote->status === 'en_cours' ? 'selected' : '' }}>En cours d'étude</option>
                            <option value="devis_envoye" {{ $quote->status === 'devis_envoye' ? 'selected' : '' }}>Devis envoyé au client</option>
                            <option value="acceptee" {{ $quote->status === 'acceptee' ? 'selected' : '' }}>Acceptée / Validée</option>
                            <option value="refusee" {{ $quote->status === 'refusee' ? 'selected' : '' }}>Refusée / Sans suite</option>
                            <option value="cloturee" {{ $quote->status === 'cloturee' ? 'selected' : '' }}>Clôturée</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info w-100">
                        Mettre à jour le statut
                    </button>
                </form>
            </div>

            <!-- Prospect Card -->
            <div class="card-admin p-4 mb-4">
                <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-15 pb-2">
                    <i class="bi bi-person text-info me-2"></i>Coordonnées du Prospect
                </h5>

                <div class="d-flex flex-column gap-3 small">
                    <div>
                        <span class="text-secondary d-block">Identité :</span>
                        <span class="text-white fw-bold fs-6">{{ $quote->prospect->first_name }} {{ $quote->prospect->last_name }}</span>
                    </div>

                    @if($quote->prospect->company)
                        <div>
                            <span class="text-secondary d-block">Entreprise :</span>
                            <span class="text-info fw-semibold">{{ $quote->prospect->company }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="text-secondary d-block">Adresse e-mail :</span>
                        <a href="mailto:{{ $quote->prospect->email }}?subject=Votre%20demande%20de%20devis%20{{ $quote->reference }}" class="text-info text-decoration-none hover-cyan">
                            {{ $quote->prospect->email }} <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>

                    <div>
                        <span class="text-secondary d-block">Téléphone :</span>
                        <a href="tel:{{ $quote->prospect->phone }}" class="text-light text-decoration-none">
                            {{ $quote->prospect->phone }} <i class="bi bi-telephone-outbound ms-1" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>

                    <div class="pt-2 border-top border-secondary border-opacity-15">
                        <span class="text-secondary d-block">Historique prospect :</span>
                        <span class="text-secondary">Enregistré le {{ $quote->prospect->created_at->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
