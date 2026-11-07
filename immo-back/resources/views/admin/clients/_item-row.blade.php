{{--
    Une ligne d'élément du portail : résumé, boutons « Modifier » (formulaire repliable) et « Supprimer ».
    Attend : $item, $type, $definition, $projects (projets du client, pour déplacer l'élément).
--}}
<div class="mb-2 rounded border border-secondary border-opacity-25">
    <div class="p-3 d-flex flex-wrap align-items-center gap-2 text-white small">
        <span class="fw-semibold">{{ $item->title }}</span>
        @foreach($definition['fields'] as $key => $field)
            @if($field['type'] === 'select' && ! ($type === 'task' && $key === 'col'))
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $field['options'][$item->field($key)] ?? $item->field($key) }}</span>
            @endif
        @endforeach
        @if($type === 'task' && $item->field('owner'))
            <span class="text-secondary">{{ $item->field('owner') }}</span>
        @endif
        @if($type === 'milestone')
            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ $item->field('progress') }} %</span>
        @endif
        @if($type === 'document' && $item->field('amount') !== null)
            <span class="text-secondary">{{ number_format((int) $item->field('amount'), 0, ',', ' ') }} FCFA</span>
        @endif
        @if($item->file())
            <a href="{{ route('admin.portal-items.file', $item) }}" target="_blank" rel="noopener" class="text-info text-decoration-none"><i class="bi bi-paperclip"></i> {{ $item->file()['name'] }}</a>
        @elseif($type === 'document' && $item->field('url'))
            <a href="{{ $item->field('url') }}" target="_blank" rel="noopener noreferrer" class="text-info text-decoration-none"><i class="bi bi-box-arrow-up-right"></i> Lien</a>
        @endif
        @if($type === 'ticket' && $item->field('status') === 'open')
            <span class="badge bg-warning text-dark">À traiter</span>
        @endif
        @if(! empty($item->data['signedAt']))
            <span class="text-secondary">Signé le {{ \Illuminate\Support\Carbon::parse($item->data['signedAt'])->format('d/m/Y') }} par {{ $item->data['signedBy'] ?? 'le client' }}</span>
        @endif
        <span class="text-secondary ms-auto">{{ $item->created_at->format('d/m/Y') }}</span>
        <button class="btn btn-sm btn-outline-info py-0" type="button" data-bs-toggle="collapse" data-bs-target="#edit-item-{{ $item->id }}" aria-expanded="false" aria-controls="edit-item-{{ $item->id }}">
            <i class="bi bi-pencil me-1"></i>Modifier
        </button>
        <form action="{{ route('admin.portal-items.destroy', $item) }}" method="POST" onsubmit="return confirm('Supprimer « {{ addslashes($item->title) }} » ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash me-1"></i>Supprimer</button>
        </form>
    </div>
    <div id="edit-item-{{ $item->id }}" class="collapse">
        <form action="{{ route('admin.portal-items.update', $item) }}" method="POST" enctype="multipart/form-data" class="p-3 pt-0">
            @csrf
            @method('PUT')
            @include('admin.clients._item-fields', ['item' => $item, 'prefix' => "item{$item->id}"])
            <button type="submit" class="btn btn-sm btn-info mt-3"><i class="bi bi-save me-1"></i>Enregistrer</button>
        </form>
    </div>
</div>
