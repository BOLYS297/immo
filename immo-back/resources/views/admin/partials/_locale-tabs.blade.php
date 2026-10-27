{{-- Onglets de langue d'un formulaire. Attend : $prefix (préfixe d'id des panneaux). L'onglet signale les erreurs de sa langue. --}}
<ul class="nav nav-tabs border-secondary border-opacity-25 mb-3" role="tablist">
    @foreach(config('cms.locales') as $locale => $name)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $prefix }}-{{ $locale }}" type="button" role="tab">
                {{ $name }}
                @if($errors->has("*.$locale"))<i class="bi bi-exclamation-circle-fill text-danger ms-1"></i>@endif
            </button>
        </li>
    @endforeach
</ul>
