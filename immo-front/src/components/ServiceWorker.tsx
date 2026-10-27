'use client';
import { useEffect } from 'react';

/** Enregistre le service worker (production uniquement, pour ne pas gêner le rechargement à chaud en développement). */
export default function ServiceWorker() {
  useEffect(() => {
    if (process.env.NODE_ENV !== 'production' || !('serviceWorker' in navigator)) return;
    const register = () => navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    if (document.readyState === 'complete') register();
    else window.addEventListener('load', register, { once: true });
  }, []);
  return null;
}
