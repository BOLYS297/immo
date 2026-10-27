@extends('layouts.admin')

@section('title', 'Utilisateur : ' . $member->name)
@section('page_title', 'Utilisateur : ' . $member->name)

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour à l'équipe</a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4" style="max-width: 860px;">
        <form action="{{ route('admin.users.update', $member) }}" method="POST" data-dirty-guard>
            @csrf
            @method('PUT')
            @include('admin.users._form')
            <button type="submit" class="btn btn-info px-4"><i class="bi bi-save me-1"></i>Enregistrer</button>
        </form>
    </div>

@endsection
