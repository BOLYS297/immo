{{-- Aperçu de l'icône Lucide saisie : rendue par le front (immo-front /api/icons/{nom}). --}}
@push('scripts')
    <script>
        document.querySelectorAll('[data-icon-input]').forEach((input) => {
            const preview = input.parentElement.querySelector('[data-icon-preview]');
            let timer = null;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const name = input.value.trim();
                    if (/^[a-z0-9]+(-[a-z0-9]+)*$/.test(name)) preview.src = input.dataset.iconBase + name;
                }, 300);
            });
        });
    </script>
@endpush
