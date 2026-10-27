@extends('layouts.admin')

@section('title', 'Nouveau service')
@section('page_title', 'Nouveau service')

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Retour aux services</a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-900">
        <form action="{{ route('admin.services.store') }}" method="POST" data-dirty-guard>
            @csrf
            @include('admin.services._form')
            <button type="submit" class="btn btn-info px-4 py-2"><i class="bi bi-plus-lg me-1"></i>Créer</button>
        </form>
    </div>

@endsection
