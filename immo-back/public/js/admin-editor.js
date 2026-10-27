/*
 * Confort d'édition de l'administration (chargé par layouts/admin.blade.php).
 *  - [data-sortable="URL"] : liste réordonnable au glisser-déposer (souris, doigt) par sa poignée
 *    [data-sortable-handle] ; envoie le nouvel ordre (PATCH {ids}) puis recharge la page.
 *    Les boutons monter / descendre restent l'alternative au clavier.
 *  - form[data-dirty-guard] : avertit avant de quitter la page si le formulaire a été modifié ; Ctrl+S / Cmd+S l'enregistre.
 *  - [data-recommended="N"] : compteur de caractères avec la longueur recommandée (titres et descriptions SEO).
 */
(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // ─── Glisser-déposer ───
  document.querySelectorAll('[data-sortable]').forEach((list) => {
    const items = () => [...list.children].filter((el) => el.matches('[data-sortable-item]'));
    const order = () => items().map((el) => el.dataset.id);

    list.addEventListener('pointerdown', (event) => {
      const handle = event.target.closest('[data-sortable-handle]');
      const item = handle?.closest('[data-sortable-item]');
      if (!item || item.parentElement !== list) return; // poignée d'une liste imbriquée : elle gère elle-même
      event.preventDefault();
      const before = order().join();
      item.classList.add('is-dragging');
      // Écoute sur la fenêtre : déplacer l'élément dans le DOM ferait perdre une capture du pointeur sur la poignée.

      const move = (e) => {
        if (e.pointerId !== event.pointerId) return;
        const next = items().find((el) => el !== item && e.clientY < el.getBoundingClientRect().top + el.offsetHeight / 2);
        if (next) {
          if (next !== item.nextElementSibling) list.insertBefore(item, next);
        } else if (item !== list.lastElementChild) {
          list.append(item);
        }
      };
      const drop = async () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', drop);
        window.removeEventListener('pointercancel', drop);
        item.classList.remove('is-dragging');
        if (order().join() === before) return;
        list.setAttribute('aria-busy', 'true');
        try {
          const response = await fetch(list.dataset.sortable, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ ids: order().map(Number) }),
          });
          if (!response.ok) throw new Error(String(response.status));
        } catch {
          alert('Le nouvel ordre n’a pas pu être enregistré. La page va être rechargée.');
        }
        window.location.reload(); // numéros, boutons et état de publication à jour
      };
      window.addEventListener('pointermove', move);
      window.addEventListener('pointerup', drop);
      window.addEventListener('pointercancel', drop);
    });
  });

  // ─── Modifications non enregistrées ───
  const guarded = [...document.querySelectorAll('form[data-dirty-guard]')];
  let dirty = false;
  let submitting = false;
  guarded.forEach((form) => {
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('click', (e) => { if (e.target.closest('[data-repeater-add], [data-repeater-remove], [data-repeater-up], [data-repeater-down], [data-media-clear]')) dirty = true; });
    form.addEventListener('submit', () => { submitting = true; });
  });
  window.addEventListener('beforeunload', (event) => {
    if (dirty && !submitting) {
      event.preventDefault();
      event.returnValue = ''; // le navigateur affiche son propre message
    }
  });

  // Ctrl+S / Cmd+S : enregistre le formulaire en cours d'édition (celui qui a le focus, sinon le premier).
  document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's' || guarded.length === 0) return;
    event.preventDefault();
    const form = document.activeElement?.closest('form[data-dirty-guard]') ?? guarded[0];
    form.requestSubmit();
  });

  // ─── Compteurs de caractères ───
  document.querySelectorAll('[data-recommended]').forEach((field) => {
    const max = Number(field.dataset.recommended);
    const counter = document.createElement('div');
    counter.className = 'form-text char-counter';
    counter.setAttribute('aria-live', 'polite');
    field.insertAdjacentElement('afterend', counter);
    const update = () => {
      const n = field.value.length;
      counter.textContent = `${n} / ${max} caractères recommandés${n > max ? ' — risque d’être coupé dans Google' : ''}`;
      counter.classList.toggle('is-over', n > max);
    };
    field.addEventListener('input', update);
    update();
  });
})();
