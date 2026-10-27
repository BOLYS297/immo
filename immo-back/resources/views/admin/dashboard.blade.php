@extends('layouts.admin')

@section('title', 'Tableau de Bord')
@section('page_title', 'Vue d\'Ensemble & Indicateurs')

@section('content')

    <!-- Quick Stats Cards Row -->
    <div class="row g-3 mb-4">
        @can('leads.manage')
        <!-- New Quotes Alert Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="card-admin p-3 position-relative overflow-hidden h-100 border-start border-4 border-danger">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.75rem;">Devis en attente</span>
                    <div class="rounded-3 p-2 bg-danger bg-opacity-15 text-danger">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-2 fw-bold text-white">{{ $stats['new_quotes'] }}</div>
                    <span class="small text-danger">À traiter en priorité</span>
                </div>
                <div class="small text-secondary mt-1">Total : {{ $stats['total_quotes'] }} demandes</div>
            </div>
        </div>

        <!-- In progress Quotes Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="card-admin p-3 position-relative overflow-hidden h-100 border-start border-4 border-warning">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.75rem;">Devis en cours</span>
                    <div class="rounded-3 p-2 bg-warning bg-opacity-15 text-warning">
                        <i class="bi bi-gear fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-2 fw-bold text-white">{{ $stats['in_progress_quotes'] }}</div>
                    <span class="small text-warning">En analyse / chiffrage</span>
                </div>
                <div class="small text-secondary mt-1">Dossiers techniques actifs</div>
            </div>
        </div>

        <!-- Contact Messages Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="card-admin p-3 position-relative overflow-hidden h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.75rem;">Nouveaux Messages</span>
                    <div class="rounded-3 p-2 bg-primary bg-opacity-15 text-primary">
                        <i class="bi bi-chat-left-dots fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-2 fw-bold text-white">{{ $stats['new_messages'] }}</div>
                    <span class="small text-primary">Non lus</span>
                </div>
                <div class="small text-secondary mt-1">Formulaire de contact</div>
            </div>
        </div>

        @endcan

        <!-- Catalog Overview Card -->
        <div class="col-xl-3 col-sm-6">
            <div class="card-admin p-3 position-relative overflow-hidden h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.75rem;">Catalogue Actif</span>
                    <div class="rounded-3 p-2 bg-info bg-opacity-15 text-info">
                        <i class="bi bi-stack fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="fs-2 fw-bold text-white">{{ $stats['total_services'] }}</div>
                    <span class="small text-info">Services • {{ $stats['total_projects'] }} Réalisations</span>
                </div>
                <div class="small text-secondary mt-1">{{ $stats['total_posts'] }} Articles de blog publiés</div>
            </div>
        </div>
    </div>

    @if($pendingPages->isNotEmpty() || $pendingPosts->isNotEmpty())
        {{-- File de relecture : brouillons préparés par l'équipe, à publier --}}
        <section class="card-admin p-4 mb-4" aria-labelledby="pending-title">
            <h5 id="pending-title" class="text-white fw-bold mb-3"><i class="bi bi-hourglass-split text-warning me-2"></i>À relire et publier</h5>
            <div class="row g-4">
                @if($pendingPages->isNotEmpty())
                    <div class="col-lg-6">
                        <h6 class="small text-secondary text-uppercase fw-semibold">Pages modifiées</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($pendingPages as $page)
                                <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom border-secondary border-opacity-25">
                                    <span>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-white fw-semibold text-decoration-none">{{ $page->getTranslation('title', config('cms.default_locale')) }}</a>
                                        <span class="small text-secondary d-block">{{ $page->publishedRevision ? 'Modifiée ' : 'Jamais publiée — créée ' }}{{ $page->updated_at->diffForHumans() }}</span>
                                    </span>
                                    <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info flex-shrink-0"><i class="bi bi-eye me-1"></i>Aperçu</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($pendingPosts->isNotEmpty())
                    <div class="col-lg-6">
                        <h6 class="small text-secondary text-uppercase fw-semibold">Articles en brouillon</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($pendingPosts as $post)
                                <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom border-secondary border-opacity-25">
                                    <span>
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="text-white fw-semibold text-decoration-none">{{ Str::limit($post->getTranslation('title', config('cms.default_locale')), 60) }}</a>
                                        <span class="small text-secondary d-block">{{ $post->author?->name ?? '—' }} · {{ $post->updated_at->diffForHumans() }}</span>
                                    </span>
                                    <a href="{{ route('admin.posts.preview', $post) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info flex-shrink-0"><i class="bi bi-eye me-1"></i>Aperçu</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <!-- Quick Shortcuts Row -->
    <div class="card-admin p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span class="text-secondary small fw-semibold text-uppercase" style="font-size: 0.75rem;">Actions Rapides :</span>
            <div class="d-flex flex-wrap gap-2">
                @can('leads.manage')
                <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-file-earmark-text me-1"></i>Gérer les devis
                </a>
                @endcan
                @can('content.manage')
                <a href="{{ route('admin.services.create') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nouveau service
                </a>
                @endcan
                @can('content.manage')
                <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-plus-circle me-1"></i>Nouveau projet portfolio
                </a>
                @endcan
                @can('posts.write')
                <a href="{{ route('admin.posts.create') }}" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-pencil-square me-1"></i>Rédiger un article
                </a>
                @endcan
                @can('settings.manage')
                <a href="{{ route('admin.settings.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-sliders me-1"></i>Paramètres du site
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Tables Row: Latest Quotes & Latest Messages -->
    @can('leads.manage')
    <div class="row g-4">
        <!-- Latest Quotes -->
        <div class="col-xl-7">
            <div class="card-admin p-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="bi bi-file-earmark-text text-danger me-2"></i>Dernières demandes de devis
                    </h5>
                    <a href="{{ route('admin.quotes.index') }}" class="btn btn-sm btn-outline-secondary">
                        Voir tout &rarr;
                    </a>
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table table-dark table-hover align-middle small mb-0">
                        <thead>
                            <tr class="text-secondary border-secondary border-opacity-25">
                                <th>Réf.</th>
                                <th>Prospect</th>
                                <th>Services</th>
                                <th>Statut</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestQuotes as $quote)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.quotes.show', $quote->id) }}" class="font-monospace text-info text-decoration-none fw-semibold">
                                            {{ $quote->reference }}
                                        </a>
                                        <div class="text-secondary" style="font-size: 0.72rem;">{{ $quote->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <div class="text-white fw-semibold">
                                            {{ $quote->prospect->last_name }} {{ $quote->prospect->first_name }}
                                        </div>
                                        <div class="text-secondary" style="font-size: 0.75rem;">
                                            {{ $quote->prospect->company ?: $quote->prospect->email }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-25 text-light">
                                            {{ $quote->services->count() }} service(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-{{ $quote->status }} px-2 py-1">
                                            {{ $quote->status_label }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.quotes.show', $quote->id) }}" class="btn btn-sm btn-outline-info">
                                            Consulter
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">
                                        Aucune demande de devis enregistrée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Latest Contact Messages -->
        <div class="col-xl-5">
            <div class="card-admin p-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="text-white fw-bold mb-0">
                        <i class="bi bi-chat-left-dots text-primary me-2"></i>Derniers messages
                    </h5>
                    <a href="{{ route('admin.contact.index') }}" class="btn btn-sm btn-outline-secondary">
                        Tous les messages &rarr;
                    </a>
                </div>

                <div class="d-flex flex-column gap-3 flex-grow-1">
                    @forelse($latestMessages as $msg)
                        <a href="{{ route('admin.contact.show', $msg->id) }}" class="p-3 rounded-3 bg-dark bg-opacity-40 border border-secondary border-opacity-15 text-decoration-none hover-cyan d-flex align-items-start justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="text-white fw-semibold small">{{ $msg->name }}</span>
                                    @if($msg->status === 'nouveau')
                                        <span class="badge bg-danger" style="font-size: 0.65rem;">Nouveau</span>
                                    @endif
                                </div>
                                <div class="text-info small fw-medium">{{ Str::limit($msg->subject, 35) }}</div>
                                <div class="text-secondary small mt-1" style="font-size: 0.75rem;">
                                    {{ Str::limit($msg->message, 60) }}
                                </div>
                            </div>
                            <span class="text-secondary small font-monospace" style="font-size: 0.7rem;">
                                {{ $msg->created_at->diffForHumans() }}
                            </span>
                        </a>
                    @empty
                        <div class="text-center text-secondary py-4">
                            Aucun message de contact.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endcan

@endsection
