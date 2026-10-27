@extends('layouts.admin')

@section('title', 'Utilisateurs & rôles')
@section('page_title', 'Utilisateurs & rôles')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Membres de l'équipe ayant accès à l'administration. Les clients du portail se gèrent dans « Clients ».</p>
        <a href="{{ route('admin.users.create') }}" class="btn btn-info"><i class="bi bi-person-plus me-1"></i>Nouvel utilisateur</a>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card-admin p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Articles</th>
                                <th scope="col">État</th>
                                <th scope="col" class="text-end"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                                <tr class="{{ $member->is_active ? '' : 'opacity-50' }}">
                                    <td>
                                        <div class="fw-semibold text-white">{{ $member->name }} @if($member->is(auth()->user()))<span class="badge bg-info text-dark ms-1">Vous</span>@endif</div>
                                        <div class="small text-secondary">{{ $member->email }}</div>
                                    </td>
                                    <td>{{ $member->roleLabel() }}</td>
                                    <td class="font-monospace text-secondary">{{ $member->posts_count }}</td>
                                    <td>
                                        @if($member->is_active)
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Actif</span>
                                        @else
                                            <span class="badge bg-secondary">Désactivé</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.users.edit', $member) }}" class="btn btn-sm btn-info" title="Modifier" aria-label="Modifier {{ $member->name }}"><i class="bi bi-pencil"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Ce que chaque rôle permet, lu dans config/admin.php --}}
        <div class="col-xl-4">
            <div class="card-admin p-4">
                <h6 class="text-white fw-bold mb-3">Rôles</h6>
                @foreach($roles as $role)
                    <div class="{{ $loop->last ? '' : 'mb-3 pb-3 border-bottom border-secondary border-opacity-25' }}">
                        <div class="fw-semibold text-white small">{{ $role['label'] }}</div>
                        <div class="small text-secondary">{{ $role['description'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection
