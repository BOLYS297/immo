{{--
    Fenêtre de sélection d'image, partagée par les formulaires qui ont des champs « image » (data-media-field).
    Charge la médiathèque en JSON (route admin.media.index ?picker=1) et permet d'envoyer une image sans quitter le formulaire.
--}}
<div class="modal fade" id="mediaPicker" tabindex="-1" aria-labelledby="mediaPickerTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content card-admin border-0">
            <div class="modal-header" style="border-color: var(--line);">
                <h2 class="modal-title h5" id="mediaPickerTitle">Médiathèque</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <label class="btn btn-info mb-0">
                        <i class="bi bi-cloud-arrow-up me-1"></i>Envoyer une image
                        <input type="file" accept="{{ collect(config('cms.media.mimes'))->map(fn ($m) => '.'.$m)->implode(',') }}" class="d-none" data-picker-upload>
                    </label>
                    <input type="search" class="form-control" style="max-width: 320px;" placeholder="Rechercher par nom de fichier" aria-label="Rechercher une image" data-picker-search>
                    <span class="small text-secondary" data-picker-status role="status"></span>
                </div>
                <div class="row g-3" data-picker-grid></div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-outline-secondary d-none" data-picker-more>Afficher plus</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const modalEl = document.getElementById('mediaPicker');
            const modal = new bootstrap.Modal(modalEl);
            const grid = modalEl.querySelector('[data-picker-grid]');
            const more = modalEl.querySelector('[data-picker-more]');
            const status = modalEl.querySelector('[data-picker-status]');
            const listUrl = @json(route('admin.media.index', ['picker' => 1]));
            const storeUrl = @json(route('admin.media.store'));
            const token = @json(csrf_token());
            let target = null, next = null, query = '';

            const tile = (m) => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-xl-3';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'card-admin p-0 w-100 overflow-hidden text-start';
                btn.title = m.name;
                btn.innerHTML = '<span class="d-block" style="aspect-ratio:4/3;background:var(--surface-2)"><img class="w-100 h-100" style="object-fit:cover" loading="lazy" alt=""></span><span class="d-block small p-2 text-truncate"></span>';
                btn.querySelector('img').src = m.url;
                btn.querySelector('span.p-2').textContent = m.name;
                btn.addEventListener('click', () => { select(m); modal.hide(); });
                col.appendChild(btn);
                return col;
            };

            async function load(url, reset) {
                status.textContent = 'Chargement…';
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const out = await res.json();
                if (reset) grid.replaceChildren();
                out.data.forEach((m) => grid.appendChild(tile(m)));
                if (!grid.children.length) grid.innerHTML = '<p class="text-secondary text-center py-4 mb-0">Aucune image. Envoyez-en une avec le bouton ci-dessus.</p>';
                next = out.next;
                more.classList.toggle('d-none', !next);
                status.textContent = '';
            }

            function select(m) {
                if (!target) return;
                const input = target.querySelector('[data-media-input]');
                input.value = m ? m.id : '';
                // Champ caché modifié par script : on le signale (aperçu en direct de la section).
                input.dispatchEvent(new Event('change', { bubbles: true }));
                const preview = target.querySelector('[data-media-preview]');
                preview.src = m ? m.url : '';
                preview.classList.toggle('d-none', !m);
                target.querySelector('[data-media-clear]').classList.toggle('d-none', !m);
                target.querySelector('[data-media-choose]').lastChild.textContent = m ? 'Changer' : 'Choisir une image';
            }

            // Délégation : fonctionne aussi pour les champs ajoutés dans les listes répétables.
            document.addEventListener('click', (e) => {
                const field = e.target.closest('[data-media-field]');
                if (!field) return;
                if (e.target.closest('[data-media-choose]')) { target = field; modal.show(); load(listUrl + (query ? '&q=' + encodeURIComponent(query) : ''), true); }
                if (e.target.closest('[data-media-clear]')) { target = field; select(null); }
            });
            more.addEventListener('click', () => next && load(next, false));

            let timer;
            modalEl.querySelector('[data-picker-search]').addEventListener('input', (e) => {
                clearTimeout(timer);
                timer = setTimeout(() => { query = e.target.value.trim(); load(listUrl + (query ? '&q=' + encodeURIComponent(query) : ''), true); }, 300);
            });

            modalEl.querySelector('[data-picker-upload]').addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;
                const body = new FormData();
                body.append('files[]', file);
                status.textContent = 'Envoi en cours…';
                const res = await fetch(storeUrl, { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token } });
                const out = await res.json().catch(() => ({}));
                e.target.value = '';
                if (!res.ok) { status.textContent = Object.values(out.errors ?? {}).flat()[0] ?? 'Envoi impossible.'; return; }
                select(out.data[0]);
                modal.hide();
            });
        })();
    </script>
@endpush
