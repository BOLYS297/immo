@extends('layouts.admin')

@section('title', 'Médiathèque')
@section('page_title', 'Médiathèque')

@section('content')

    @include('admin.pages._errors')

    <div class="card-admin p-4 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-7">
                <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end">
                    @csrf
                    <div class="flex-grow-1">
                        <label for="files" class="form-label small fw-semibold">Ajouter des images</label>
                        <input type="file" name="files[]" id="files" class="form-control" accept="{{ collect(config('cms.media.mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}" multiple required>
                    </div>
                    <button type="submit" class="btn btn-info"><i class="bi bi-cloud-arrow-up me-1"></i>Envoyer</button>
                </form>
                <div class="form-text">
                    {{ strtoupper(implode(', ', config('cms.media.mimes'))) }} · {{ round(config('cms.media.max_kb') / 1024) }} Mo maximum · jusqu'à 20 images à la fois.
                    Le site redimensionne les images automatiquement : envoyez-les en bonne qualité.
                </div>
            </div>
            <div class="col-lg-5">
                <form method="GET" class="d-flex gap-2">
                    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Rechercher par nom de fichier" aria-label="Rechercher une image">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
            </div>
        </div>
    </div>

    @if($media->isEmpty())
        <div class="card-admin p-5 text-center text-secondary">
            <i class="bi bi-images fs-1 d-block mb-2"></i>
            {{ $search ? 'Aucune image ne correspond à cette recherche.' : 'Aucune image pour le moment. Ajoutez-en avec le formulaire ci-dessus.' }}
        </div>
    @else
        <div class="row g-3">
            @foreach($media as $item)
                <div class="col-sm-6 col-lg-4 col-xxl-3">
                    <div class="card-admin h-100 overflow-hidden d-flex flex-column">
                        <a href="{{ $item->url() }}" target="_blank" rel="noopener" class="d-block" style="aspect-ratio: 4 / 3; background: var(--surface-2);">
                            <img src="{{ $item->url() }}" alt="{{ $item->getTranslation('alt', config('cms.default_locale')) }}" loading="lazy" class="w-100 h-100" style="object-fit: cover;">
                        </a>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="small fw-semibold text-truncate" title="{{ $item->original_name }}">{{ $item->original_name }}</div>
                            <div class="text-secondary" style="font-size: 0.75rem;">{{ $item->width }} × {{ $item->height }} px · {{ number_format($item->size / 1024, 0, ',', ' ') }} Ko · {{ $item->created_at->format('d/m/Y') }}</div>

                            <form action="{{ route('admin.media.update', $item) }}" method="POST" class="mt-3">
                                @csrf
                                @method('PUT')
                                @foreach(config('cms.locales') as $locale => $localeName)
                                    <label for="alt_{{ $item->id }}_{{ $locale }}" class="form-label small mb-1">Texte alternatif ({{ strtoupper($locale) }})</label>
                                    <input type="text" name="alt[{{ $locale }}]" id="alt_{{ $item->id }}_{{ $locale }}" value="{{ $item->getTranslation('alt', $locale, false) }}" maxlength="255" class="form-control form-control-sm mb-2" placeholder="{{ $locale === config('cms.default_locale') ? 'Décrivez l’image pour les malvoyants' : 'Vide = version française' }}">
                                @endforeach
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-save me-1"></i>Enregistrer</button>
                            </form>

                            <form action="{{ route('admin.media.destroy', $item) }}" method="POST" class="mt-2" onsubmit="return confirm('Supprimer définitivement cette image ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $media->links('pagination::bootstrap-5') }}
        </div>
    @endif

@endsection
