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

    @foreach($types as $type => $definition)
        @php($list = $items->get($type, collect()))
        <section id="type-{{ $type }}" class="card-admin p-4 mb-4">
            <h6 class="text-white fw-bold mb-3">
                <i class="bi {{ $definition['icon'] }} text-info me-2"></i>{{ $definition['label'] }}
                <span class="badge bg-secondary ms-1">{{ $list->count() }}</span>
            </h6>

            @foreach($list as $item)
                <details class="mb-2 rounded border border-secondary border-opacity-25">
                    <summary class="p-3 d-flex flex-wrap align-items-center gap-2 text-white small" style="cursor: pointer;">
                        <span class="fw-semibold">{{ $item->title }}</span>
                        @foreach($definition['fields'] as $key => $field)
                            @if($field['type'] === 'select')
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $field['options'][$item->field($key)] ?? $item->field($key) }}</span>
                            @endif
                        @endforeach
                        @if($type === 'milestone')
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $item->field('progress') }} %</span>
                        @endif
                        @if($type === 'ticket' && $item->field('status') === 'open')
                            <span class="badge bg-warning text-dark">À traiter</span>
                        @endif
                        @if(! empty($item->data['signedAt']))
                            <span class="text-secondary">Signé le {{ \Illuminate\Support\Carbon::parse($item->data['signedAt'])->format('d/m/Y') }} par {{ $item->data['signedBy'] ?? 'le client' }}</span>
                        @endif
                        <span class="text-secondary ms-auto">{{ $item->created_at->format('d/m/Y') }}</span>
                    </summary>
                    <div class="p-3 pt-0">
                        <form action="{{ route('admin.portal-items.update', $item) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('admin.clients._item-fields', ['item' => $item, 'prefix' => "item{$item->id}"])
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-info"><i class="bi bi-save me-1"></i>Enregistrer</button>
                            </div>
                        </form>
                        <form action="{{ route('admin.portal-items.destroy', $item) }}" method="POST" class="mt-2" onsubmit="return confirm('Supprimer cet élément du portail ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Supprimer</button>
                        </form>
                    </div>
                </details>
            @endforeach

            <details class="mt-3" @if($list->isEmpty()) open @endif>
                <summary class="btn btn-sm btn-outline-info"><i class="bi bi-plus-lg me-1"></i>Ajouter : {{ $definition['item_label'] }}</summary>
                <form action="{{ route('admin.clients.items.store', $client) }}" method="POST" class="mt-3 p-3 rounded border border-secondary border-opacity-25">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    @include('admin.clients._item-fields', ['item' => null, 'prefix' => "new{$type}"])
                    <button type="submit" class="btn btn-sm btn-info mt-3"><i class="bi bi-plus-lg me-1"></i>Ajouter</button>
                </form>
            </details>
        </section>
    @endforeach

@endsection
