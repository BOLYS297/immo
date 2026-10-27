'use client';
import { useSyncExternalStore } from 'react';

const QUERY = '(prefers-reduced-motion: reduce)';

function subscribe(onChange: () => void) {
  const media = matchMedia(QUERY);
  media.addEventListener('change', onChange);
  return () => media.removeEventListener('change', onChange);
}

/**
 * true si le visiteur demande moins d'animations.
 * Vaut false au rendu serveur ET pendant l'hydratation (useReducedMotion de Framer Motion lit la préférence
 * dès le premier rendu client : le HTML ne correspond plus, React refait toute la page et perd la classe `dark`).
 * La préférence réelle s'applique juste après l'hydratation.
 */
export function usePrefersReducedMotion(): boolean {
  return useSyncExternalStore(subscribe, () => matchMedia(QUERY).matches, () => false);
}
