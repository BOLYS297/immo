/*
 * Bouton afficher / masquer sur chaque champ mot de passe de l'administration (connexion, comptes, clients…).
 * Chargé par layouts/admin.blade.php et admin/auth/login.blade.php ; aucun balisage à ajouter dans les formulaires.
 */
(() => {
  const LABELS = { show: 'Afficher le mot de passe', hide: 'Masquer le mot de passe' };

  document.querySelectorAll('input[type="password"]').forEach((input) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'password-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.setAttribute('aria-controls', input.id || '');
    const render = (visible) => {
      button.setAttribute('aria-pressed', String(visible));
      button.setAttribute('aria-label', visible ? LABELS.hide : LABELS.show);
      button.title = visible ? LABELS.hide : LABELS.show;
      button.innerHTML = `<i class="bi ${visible ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
    };
    button.addEventListener('click', () => {
      const visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      render(visible);
      input.focus();
    });
    // Toujours remasqué à l'envoi : le navigateur ne mémorise pas le champ comme du texte.
    input.form?.addEventListener('submit', () => { input.type = 'password'; render(false); });
    render(false);
    wrapper.append(button);
  });
})();
