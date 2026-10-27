@extends('layouts.admin')

@section('title', 'Gestion des Avis & Témoignages')
@section('page_title', 'Avis & Témoignages Clients')

@section('content')

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="text-white fw-bold mb-1">Retours & Recommandations Clients</h5>
            <p class="text-secondary small mb-0">Affichés sur la page Recommandations du site, uniquement avec l'accord écrit du client</p>
        </div>
        <a href="{{ route('admin.testimonials.create') }}" class="btn btn-info">
            <i class="bi bi-plus-lg me-1"></i>Ajouter un Avis
        </a>
    </div>

    <div class="card-admin p-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle small mb-0">
                <thead>
                    <tr class="text-secondary border-secondary border-opacity-25">
                        <th>Ordre</th>
                        <th>Client & Entreprise</th>
                        <th>Note</th>
                        <th>Témoignage</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($testimonials as $t)
                        <tr>
                            <td class="font-monospace text-secondary" style="width: 60px;">
                                #{{ $t->sort_order }}
                            </td>
                            <td>
                                <div class="text-white fw-bold fs-6">{{ $t->client_name }}</div>
                                <div class="text-info" style="font-size: 0.75rem;">
                                    {{ collect([$t->role, $t->company])->filter()->implode(', ') ?: 'Client' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $t->rating)
                                            <i class="bi bi-star-fill"></i>
                                        @else
                                            <i class="bi bi-star"></i>
                                        @endif
                                    @endfor
                                </div>
                            </td>
                            <td>
                                <div class="text-secondary fst-italic" style="max-width: 400px;">
                                    "{{ Str::limit($t->getTranslation('content', config('cms.default_locale')), 120) }}"
                                </div>
                            </td>
                            <td>
                                @if($t->is_published)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Visible</span>
                                @else
                                    <span class="badge bg-secondary">Masqué</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('admin.testimonials.edit', $t->id) }}" class="btn btn-sm btn-info" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.testimonials.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Supprimer ce témoignage ?');">
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
                            <td colspan="6" class="text-center text-secondary py-5">
                                Aucun avis enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $testimonials->links('pagination::bootstrap-5') }}
        </div>
    </div>

@endsection
