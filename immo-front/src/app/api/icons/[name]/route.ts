import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { lucideIcon } from '@/lib/lucide';

/**
 * Aperçu d'une icône Lucide dans l'admin Laravel (fiche d'un service) : SVG rendu par le serveur.
 * Nom inconnu : 404, l'admin voit tout de suite que l'icône n'existe pas.
 */
export function GET(_req: Request, { params }: { params: { name: string } }) {
  const Icon = lucideIcon(params.name);
  if (!Icon) return new Response('Icône inconnue', { status: 404, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });

  const svg = renderToStaticMarkup(createElement(Icon, { size: 32, color: '#273480', strokeWidth: 2 }));
  // Lucide pose déjà xmlns : le SVG est autonome, affichable dans une balise <img>.
  return new Response(svg, {
    headers: { 'Content-Type': 'image/svg+xml', 'Cache-Control': 'public, max-age=86400', 'X-Content-Type-Options': 'nosniff' },
  });
}
