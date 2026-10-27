@extends('layouts.admin')

@section('title', 'Modifier le Témoignage de ' . $testimonial->client_name)
@section('page_title', 'Modifier : ' . $testimonial->client_name)

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour aux avis
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="card-admin p-4 p-md-5 max-w-800">
        <form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.testimonials._form')

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-save me-1"></i>Enregistrer les modifications
                </button>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

    @include('admin.media._picker')

@endsection
