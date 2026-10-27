@extends('layouts.admin')

@php($default = config('cms.default_locale'))

@section('title', 'Section : ' . $block['label'])
@section('page_title', 'Section : ' . $block['label'])

@section('content')

    <div class="mb-4">
        <a href="{{ route('admin.pages.edit', $section->page) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour à la page « {{ $section->page->getTranslation('title', $default) }} »
        </a>
    </div>

    @include('admin.pages._errors')

    <div class="row g-4 align-items-start">
    <div class="col-xl-6">
    <div class="card-admin p-4 p-md-4">
        <p class="text-secondary small mb-4">
            <i class="bi bi-info-circle me-1"></i>
            Un champ laissé vide reprend la version {{ $locales[$default] }}, puis le texte par défaut du site.
            Les textes d'interface (boutons de formulaire, messages d'erreur…) restent gérés dans le code du front.
        </p>

        <form action="{{ route('admin.sections.update', $section) }}" method="POST" id="section-form" data-dirty-guard>
            @csrf
            @method('PUT')

            @if(! empty($block['settings']))
                {{-- Présentation : commune aux deux langues --}}
                @php($current = $section->resolvedSettings())
                <fieldset class="mb-4 p-3 rounded-3" style="background: var(--surface-2);" data-look>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <legend class="form-label small fw-semibold mb-0 float-none w-auto">Présentation</legend>
                        {{-- Efface les propriétés de présentation : valeurs par défaut, pas d'image de fond. À enregistrer ensuite. --}}
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-look-reset><i class="bi bi-arrow-counterclockwise me-1"></i>Revenir aux réglages par défaut</button>
                    </div>
                    <div class="row g-3">
                        @foreach($block['settings'] as $key => $setting)
                            @if(($setting['type'] ?? 'select') === 'image')
                                <div class="col-12">
                                    @include('admin.pages.sections._field', ['field' => $setting, 'name' => "settings[$key]", 'id' => "settings_{$key}", 'value' => old("settings.$key", $current[$key])])
                                    <div class="form-text">Affichée derrière tout le contenu de la section ; choisissez le voile pour garder le texte lisible.</div>
                                </div>
                            @else
                                <div class="col-sm-6">
                                    <label for="settings_{{ $key }}" class="form-label small">{{ $setting['label'] }}</label>
                                    <select name="settings[{{ $key }}]" id="settings_{{ $key }}" class="form-select" data-default="{{ $setting['default'] }}">
                                        @foreach($setting['options'] as $value => $optionLabel)
                                            <option value="{{ $value }}" @selected((string) old("settings.$key", $current[$key]) === (string) $value)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <ul class="nav nav-tabs border-secondary border-opacity-25 mb-4" role="tablist">
                @foreach($locales as $locale => $localeName)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#content-{{ $locale }}" type="button" role="tab">
                            {{ $localeName }}
                            @if($errors->has("content.$locale.*"))<i class="bi bi-exclamation-circle-fill text-danger ms-1"></i>@endif
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mb-4">
                @foreach($locales as $locale => $localeName)
                    @php($saved = $section->contentFor($locale))
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="content-{{ $locale }}" role="tabpanel">
                        @foreach($block['fields'] as $key => $field)
                            @if($field['type'] === 'repeater')
                                @php($items = old("content.$locale.$key", $saved[$key] ?? []))
                                <fieldset class="mb-4" data-repeater data-max="{{ $field['max'] ?? 12 }}">
                                    <legend class="form-label text-white small fw-semibold mb-2">{{ $field['label'] }}</legend>
                                    <div data-repeater-items>
                                        @foreach(array_values(is_array($items) ? $items : []) as $index => $item)
                                            @include('admin.pages.sections._repeater-item', ['index' => $index, 'item' => $item])
                                        @endforeach
                                    </div>
                                    <template data-repeater-template>
                                        @include('admin.pages.sections._repeater-item', ['index' => '__INDEX__', 'item' => []])
                                    </template>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-repeater-add>
                                        <i class="bi bi-plus-lg me-1"></i>Ajouter : {{ Str::lower($field['item_label']) }}
                                    </button>
                                </fieldset>
                            @else
                                <div class="mb-3">
                                    @include('admin.pages.sections._field', [
                                        'name' => "content[$locale][$key]",
                                        'id' => "content_{$locale}_{$key}",
                                        'value' => old("content.$locale.$key", $saved[$key] ?? ''),
                                    ])
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="form-check form-switch mb-4">
                <input type="hidden" name="is_visible" value="0">
                <input class="form-check-input" type="checkbox" role="switch" name="is_visible" id="is_visible" value="1" {{ old('is_visible', $section->is_visible) ? 'checked' : '' }}>
                <label class="form-check-label text-white small" for="is_visible">Section affichée sur le site</label>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-info px-4 py-2">
                    <i class="bi bi-save me-1"></i>Enregistrer en brouillon
                </button>
                <a href="{{ route('admin.pages.edit', $section->page) }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
    </div>

    {{-- Aperçu en direct : le brouillon de la page rendu par le front, mis à jour pendant la saisie (non enregistrée). --}}
    <div class="col-xl-6">
        <section class="card-admin p-3 preview-panel" aria-labelledby="preview-title">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <h2 id="preview-title" class="h6 text-white fw-bold mb-0"><i class="bi bi-eye me-1 text-info"></i>Aperçu en direct</h2>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Taille de l'aperçu">
                        <button type="button" class="btn btn-outline-secondary active" data-preview-device="desktop" aria-pressed="true"><i class="bi bi-laptop"></i><span class="visually-hidden">Ordinateur</span></button>
                        <button type="button" class="btn btn-outline-secondary" data-preview-device="mobile" aria-pressed="false"><i class="bi bi-phone"></i><span class="visually-hidden">Mobile</span></button>
                    </div>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Ouvrir l'aperçu dans un onglet" data-preview-open><i class="bi bi-box-arrow-up-right"></i><span class="visually-hidden">Ouvrir dans un onglet</span></a>
                </div>
            </div>
            <p class="small text-secondary mb-2" data-preview-status role="status" aria-live="polite">Brouillon de la page — vos saisies apparaissent ici avant d'être enregistrées.</p>
            <div class="preview-frame" data-preview-frame>
                <iframe src="{{ $previewUrl }}" title="Aperçu du brouillon de la page" loading="lazy"></iframe>
            </div>
        </section>
    </div>
    </div>

    @include('admin.media._picker')

@endsection

@push('styles')
    <style>
        /* Aperçu : le site est rendu à sa largeur réelle (ordinateur 1280 px, mobile 390 px) puis réduit pour tenir dans le panneau. */
        .preview-panel { position: sticky; top: 88px; }
        .preview-frame { position: relative; height: 70vh; overflow: hidden; border: 1px solid var(--line); border-radius: 1rem; background: #F4F4F4; }
        .preview-frame iframe { position: absolute; top: 0; left: 0; border: 0; transform-origin: 0 0; background: #F4F4F4; }
    </style>
@endpush

@push('scripts')
    <script>
        // « Revenir aux réglages par défaut » : listes à leur valeur par défaut, image de fond retirée.
        document.querySelector('[data-look-reset]')?.addEventListener('click', () => {
            const look = document.querySelector('[data-look]');
            look.querySelectorAll('select[data-default]').forEach((select) => { select.value = select.dataset.default; });
            look.querySelectorAll('[data-media-clear]:not(.d-none)').forEach((button) => button.click());
            look.querySelector('select')?.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Aperçu en direct : envoie la saisie (non enregistrée) à Laravel, qui la garde avec le jeton d'aperçu,
        // puis recharge l'iframe sur la section modifiée.
        (() => {
            const form = document.getElementById('section-form');
            const frame = document.querySelector('[data-preview-frame]');
            const iframe = frame.querySelector('iframe');
            const status = document.querySelector('[data-preview-status]');
            const base = @json($previewUrl).split('#')[0];
            const anchor = @json("section-{$section->id}");
            const widths = { desktop: 1280, mobile: 390 };
            let device = 'desktop', timer = null, sent = 0;

            const fit = () => {
                const width = widths[device];
                const scale = Math.min(1, frame.clientWidth / width);
                iframe.style.width = width + 'px';
                iframe.style.height = (frame.clientHeight / scale) + 'px';
                iframe.style.transform = `scale(${scale})`;
                iframe.style.left = Math.max(0, (frame.clientWidth - width * scale) / 2) + 'px';
            };
            new ResizeObserver(fit).observe(frame);

            document.querySelectorAll('[data-preview-device]').forEach((button) => button.addEventListener('click', () => {
                device = button.dataset.previewDevice;
                document.querySelectorAll('[data-preview-device]').forEach((b) => { b.classList.toggle('active', b === button); b.setAttribute('aria-pressed', String(b === button)); });
                fit();
            }));

            const push = async () => {
                const id = ++sent;
                const data = new FormData(form);
                data.delete('_method');
                data.append('preview_token', base.split('jeton=')[1]);
                status.textContent = 'Mise à jour de l\'aperçu…';
                try {
                    const response = await fetch(@json(route('admin.sections.live', $section)), { method: 'POST', body: data, headers: { Accept: 'application/json' } });
                    if (id !== sent) return; // une saisie plus récente est déjà partie
                    if (response.ok) {
                        iframe.src = `${base}&v=${Date.now()}#${anchor}`;
                        status.textContent = 'Aperçu à jour (non enregistré).';
                    } else {
                        const body = await response.json().catch(() => ({}));
                        status.textContent = response.status === 422 ? 'À corriger : ' + Object.values(body.errors ?? {}).flat()[0] : (body.message ?? 'Aperçu indisponible.');
                    }
                } catch {
                    status.textContent = 'Aperçu indisponible : vérifiez la connexion.';
                }
            };
            const schedule = () => { clearTimeout(timer); timer = setTimeout(push, 700); };
            form.addEventListener('input', schedule);
            form.addEventListener('change', schedule);
            // Ajout, suppression et déplacement d'éléments d'une liste
            form.addEventListener('click', (e) => { if (e.target.closest('[data-repeater-add], [data-repeater-remove], [data-repeater-up], [data-repeater-down], [data-media-clear]')) schedule(); });
        })();
        // Listes répétables : ajout (depuis le <template>), suppression et réordonnancement.
        // L'ordre du DOM est l'ordre enregistré : le serveur réindexe les éléments.
        document.querySelectorAll('[data-repeater]').forEach((repeater) => {
            const items = repeater.querySelector('[data-repeater-items]');
            const template = repeater.querySelector('[data-repeater-template]');
            const add = repeater.querySelector('[data-repeater-add]');
            const max = Number(repeater.dataset.max);
            let next = items.children.length;

            const refresh = () => {
                [...items.children].forEach((item, i) => {
                    item.querySelector('[data-repeater-number]').textContent = i + 1;
                    item.querySelector('[data-repeater-up]').disabled = i === 0;
                    item.querySelector('[data-repeater-down]').disabled = i === items.children.length - 1;
                });
                add.disabled = items.children.length >= max;
            };

            add.addEventListener('click', () => {
                const html = template.innerHTML.replaceAll('__INDEX__', 'n' + next++);
                items.insertAdjacentHTML('beforeend', html);
                refresh();
                items.lastElementChild.querySelector('input, textarea')?.focus();
            });

            items.addEventListener('click', (event) => {
                const item = event.target.closest('[data-repeater-item]');
                if (!item) return;
                if (event.target.closest('[data-repeater-remove]')) item.remove();
                else if (event.target.closest('[data-repeater-up]')) item.previousElementSibling?.before(item);
                else if (event.target.closest('[data-repeater-down]')) item.nextElementSibling?.after(item);
                else return;
                refresh();
            });

            refresh();
        });
    </script>
@endpush
