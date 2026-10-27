{{-- Pied de page : une « nouvelle colonne » n'a pas de lien, on masque les champs correspondants. --}}
<script>
    document.querySelectorAll('[data-menu-form]').forEach((form) => {
        const parent = form.querySelector('[data-menu-parent]');
        const sync = () => {
            const isColumn = parent && parent.value === '';
            form.querySelectorAll('[data-menu-link]').forEach((el) => { el.hidden = isColumn; });
            const url = form.querySelector('input[name="url"]');
            if (url) url.required = !isColumn;
        };
        parent?.addEventListener('change', sync);
        sync();
    });
</script>
