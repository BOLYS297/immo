@extends('layouts.admin')

@php($default = config('cms.default_locale'))
@php($inputClass = 'form-control bg-dark text-light border-secondary border-opacity-25')

@section('title', 'Réglages du site')
@section('page_title', 'Réglages du site')

@section('content')

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <p class="text-secondary small mb-0">Coordonnées, réseaux sociaux, bouton de l'en-tête et référencement par défaut, communs à toutes les pages du site.</p>
        @can('menus.manage')
            <a href="{{ route('admin.menus.index') }}" class="btn btn-sm btn-outline-info"><i class="bi bi-list-nested me-1"></i>Gérer les menus</a>
        @endcan
    </div>

    @include('admin.pages._errors')

    <form action="{{ route('admin.settings.update') }}" method="POST" data-dirty-guard>
        @csrf

        <div class="row g-4">
            @foreach($groups as $groupKey => $group)
                <div class="col-xl-6">
                    <section class="card-admin p-4 h-100" aria-labelledby="group-{{ $groupKey }}">
                        <h5 id="group-{{ $groupKey }}" class="text-white fw-bold mb-1"><i class="bi {{ $group['icon'] }} text-info me-2"></i>{{ $group['label'] }}</h5>
                        <p class="text-secondary small mb-3">{{ $group['help'] ?? '' }}</p>

                        @foreach($group['fields'] as $key => $field)
                            @php($translatable = $field['translatable'] ?? false)
                            @php($inputs = $translatable ? collect($locales)->keys()->mapWithKeys(fn ($l) => [$l => ["settings[$key][$l]", "settings.$key.$l", "set_{$key}_{$l}"]]) : collect([null => ["settings[$key]", "settings.$key", "set_{$key}"]]))
                            <div class="mb-3">
                                @unless($translatable)
                                    <label for="set_{{ $key }}" class="form-label text-white small fw-semibold">{{ $field['label'] }}</label>
                                @else
                                    <span class="form-label text-white small fw-semibold d-block">{{ $field['label'] }}</span>
                                @endunless
                                <div class="row g-2">
                                    @foreach($inputs as $locale => [$name, $old, $id])
                                        <div class="{{ $translatable ? 'col-md-6' : 'col-12' }}">
                                            @if($translatable)
                                                <label for="{{ $id }}" class="visually-hidden">{{ $field['label'] }} ({{ $locales[$locale] }})</label>
                                            @endif
                                            <div class="{{ $translatable ? 'input-group input-group-sm' : '' }}">
                                                @if($translatable)<span class="input-group-text font-monospace" aria-hidden="true">{{ strtoupper($locale) }}</span>@endif
                                                @php($value = old($old, \App\Services\SettingService::formValue($key, $locale ?: null)))
                                                @if($field['type'] === 'select')
                                                    <select name="{{ $name }}" id="{{ $id }}" class="form-select" @if(isset($field['help'])) aria-describedby="help_{{ $key }}" @endif>
                                                        @foreach($field['options'] as $optionValue => $optionLabel)
                                                            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif(in_array($field['type'], ['textarea', 'lines']))
                                                    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $field['type'] === 'lines' ? ($field['max'] ?? 3) : 3 }}" class="{{ $inputClass }}" @if(isset($field['help'])) aria-describedby="help_{{ $key }}" @endif>{{ $value }}</textarea>
                                                @else
                                                    <input type="{{ ['email' => 'email', 'url' => 'url', 'phone' => 'tel'][$field['type']] ?? 'text' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}"
                                                        class="{{ $inputClass }} {{ $field['type'] === 'link' ? 'font-monospace' : '' }}"
                                                        @if($field['type'] === 'link') placeholder="/contact ou https://…" @elseif($field['type'] === 'url') placeholder="https://…" @endif
                                                        @if(isset($field['help'])) aria-describedby="help_{{ $key }}" @endif>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if(isset($field['help']) || $translatable)
                                    <div id="help_{{ $key }}" class="form-text text-secondary">
                                        {{ $field['help'] ?? '' }}
                                        @if($translatable) Version {{ $locales[collect($locales)->keys()->last()] }} vide = version {{ $locales[$default] }}.@endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                </div>
            @endforeach
        </div>

        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-info px-5 py-2">
                <i class="bi bi-save me-2"></i>Enregistrer les réglages
            </button>
        </div>
    </form>

@endsection
