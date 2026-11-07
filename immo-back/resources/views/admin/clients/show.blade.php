@extends('layouts.admin')

@section('title', 'Portail : ' . ($client->company ?: $client->name))
@section('page_title', 'Portail de ' . ($client->company ?: $client->name))

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux clients
        </a>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.clients.preview', $client) }}" method="POST" target="_blank">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-info" @disabled(! $client->is_active) title="{{ $client->is_active ? 'Ouvre le portail tel que le client le voit, en lecture seule' : 'Compte désactivé' }}">
                    <i class="bi bi-eye me-1"></i>Aperçu du portail
                </button>
            </form>
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-person-gear me-1"></i>Compte
            </a>
        </div>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 mb-4">
        <div class="d-flex flex-wrap gap-4 small">
            <div><span class="text-secondary d-block">Contact</span><span class="text-white fw-semibold">{{ $client->name }}</span></div>
            <div><span class="text-secondary d-block">Email de connexion</span><a href="mailto:{{ $client->email }}" class="text-info text-decoration-none">{{ $client->email }}</a></div>
            <div><span class="text-secondary d-block">Accès</span>
                @if($client->is_active)
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Actif</span>
                @else
                    <span class="badge bg-secondary">Désactivé</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Projets du client : chaque élément de son espace appartient à un projet. --}}
    <section class="card-admin p-4 mb-4" aria-labelledby="projects-title">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h6 id="projects-title" class="text-white fw-bold mb-0"><i class="bi bi-folder2-open text-info me-2"></i>Projets <span class="badge bg-secondary ms-1">{{ $projects->count() }}</span></h6>
            <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#new-project" aria-expanded="{{ $projects->isEmpty() ? 'true' : 'false' }}" aria-controls="new-project">
                <i class="bi bi-plus-lg me-1"></i>Nouveau projet
            </button>
        </div>

        @if($projects->isNotEmpty())
            <nav class="nav nav-pills gap-2" aria-label="Projets du client">
                @foreach($projects as $p)
                    <a href="{{ route('admin.clients.show', ['client' => $client, 'projet' => $p->id]) }}" @class(['nav-link py-1 px-3 small border border-info border-opacity-25', 'active' => $project?->is($p), 'text-white' => ! $project?->is($p)]) @if($project?->is($p)) aria-current="page" @endif>
                        {{ $p->name }}
                        <span class="opacity-75 ms-1">· {{ $p->statusLabel() }} · {{ $p->items_count }}</span>
                    </a>
                @endforeach
            </nav>
        @else
            <p class="text-secondary small mb-0">Aucun projet. Créez-en un : tâches, jalons, livrables, documents et tickets du client sont rangés par projet.</p>
        @endif

        <div id="new-project" @class(['collapse', 'show' => $projects->isEmpty()])>
            <form action="{{ route('admin.clients.projects.store', $client) }}" method="POST" class="mt-3 p-3 rounded border border-secondary border-opacity-25">
                @csrf
                @include('admin.clients._project-fields', ['clientProject' => null, 'prefix' => 'newproject'])
                <button type="submit" class="btn btn-sm btn-info mt-3"><i class="bi bi-plus-lg me-1"></i>Créer le projet</button>
            </form>
        </div>
    </section>

    @if($project)
        <section class="card-admin p-4 mb-4" aria-labelledby="project-title">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h5 id="project-title" class="text-white fw-bold mb-1">{{ $project->name }}
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 align-middle ms-1">{{ $project->statusLabel() }}</span>
                    </h5>
                    @if($project->summary)<p class="text-secondary small mb-0">{{ $project->summary }}</p>@endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-project" aria-expanded="false" aria-controls="edit-project">
                        <i class="bi bi-pencil me-1"></i>Modifier le projet
                    </button>
                    <form action="{{ route('admin.client-projects.destroy', $project) }}" method="POST" onsubmit="return confirm('Supprimer le projet « {{ addslashes($project->name) }} » et ses {{ $project->items_count }} élément(s) (tâches, jalons, livrables, documents, tickets) ? Cette action est définitive.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Supprimer le projet</button>
                    </form>
                </div>
            </div>
            <div id="edit-project" class="collapse">
                <form action="{{ route('admin.client-projects.update', $project) }}" method="POST" class="mt-3 p-3 rounded border border-secondary border-opacity-25">
                    @csrf
                    @method('PUT')
                    @include('admin.clients._project-fields', ['clientProject' => $project, 'prefix' => 'project'.$project->id])
                    <button type="submit" class="btn btn-sm btn-info mt-3"><i class="bi bi-save me-1"></i>Enregistrer</button>
                </form>
            </div>
        </section>

        @foreach($types as $type => $definition)
            @php($list = $items->get($type, collect()))
            <section id="type-{{ $type }}" class="card-admin p-4 mb-4">
                <h6 class="text-white fw-bold mb-3">
                    <i class="bi {{ $definition['icon'] }} text-info me-2"></i>{{ $definition['label'] }}
                    <span class="badge bg-secondary ms-1">{{ $list->count() }}</span>
                </h6>

                @if($type === 'task')
                    {{-- Tâches rangées par colonne du Kanban, comme dans l'espace client. --}}
                    @foreach($definition['fields']['col']['options'] as $col => $colLabel)
                        @php($colItems = $list->filter(fn ($task) => (int) $task->field('col') === (int) $col))
                        <h6 class="text-secondary small text-uppercase fw-semibold mt-3 mb-2">{{ $colLabel }} <span class="badge bg-secondary bg-opacity-50 ms-1">{{ $colItems->count() }}</span></h6>
                        @forelse($colItems as $item)
                            @include('admin.clients._item-row')
                        @empty
                            <p class="text-secondary small fst-italic mb-2">Aucune tâche.</p>
                        @endforelse
                    @endforeach
                @else
                    @foreach($list as $item)
                        @include('admin.clients._item-row')
                    @endforeach
                @endif

                <div class="mt-3">
                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#new-{{ $type }}" aria-expanded="{{ $list->isEmpty() ? 'true' : 'false' }}" aria-controls="new-{{ $type }}">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter : {{ $definition['item_label'] }}
                    </button>
                    <div id="new-{{ $type }}" @class(['collapse', 'show' => $list->isEmpty()])>
                        <form action="{{ route('admin.client-projects.items.store', $project) }}" method="POST" enctype="multipart/form-data" class="mt-3 p-3 rounded border border-secondary border-opacity-25">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            @include('admin.clients._item-fields', ['item' => null, 'prefix' => "new{$type}"])
                            <button type="submit" class="btn btn-sm btn-info mt-3"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                        </form>
                    </div>
                </div>
            </section>
        @endforeach
    @endif

@endsection
