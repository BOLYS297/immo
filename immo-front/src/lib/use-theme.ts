'use client';
import { useSyncExternalStore } from 'react';

function subscribe(onChange: () => void) {
  const obs = new MutationObserver(onChange);
  obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  return () => obs.disconnect();
}
const getSnapshot = () => document.documentElement.classList.contains('dark');

/** true si le Dark Mode est actif (classe `dark` sur <html>). */
export function useIsDark(): boolean {
  return useSyncExternalStore(subscribe, getSnapshot, () => false);
}

export function toggleTheme() {
  const dark = document.documentElement.classList.toggle('dark');
  try { localStorage.setItem('immo-theme', dark ? 'dark' : 'light'); } catch { /* stockage indisponible */ }
}
