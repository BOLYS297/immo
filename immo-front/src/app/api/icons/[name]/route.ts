import { lucideIcon } from '@/lib/lucide';

type IconNode = [string, Record<string, string | number>][];

const escape = (value: string | number) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

/**
 * Tracés d'une icône Lucide : le composant (forwardRef) transmet `iconNode` au composant Icon de lucide-react.
 * Lu sans rendu React : Next interdit react-dom/server dans le dossier app.
 */
function iconNode(name: string): IconNode | undefined {
  const Icon = lucideIcon(name) as unknown as { render?: (props: object, ref: null) => { props?: { iconNode?: IconNode } } } | undefined;
  return Icon?.render?.({}, null)?.props?.iconNode;
}

/**
 * Aperçu d'une icône Lucide dans l'admin Laravel (fiche d'un service) : SVG autonome, affichable dans une balise <img>.
 * Nom inconnu : 404, l'admin voit tout de suite que l'icône n'existe pas.
 */
export function GET(_req: Request, { params }: { params: { name: string } }) {
  const node = iconNode(params.name);
  if (!node) return new Response('Icône inconnue', { status: 404, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });

  const children = node
    .map(([tag, attrs]) => `<${tag} ${Object.entries(attrs).filter(([k]) => k !== 'key').map(([k, v]) => `${k}="${escape(v)}"`).join(' ')}/>`)
    .join('');
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#273480" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${children}</svg>`;

  return new Response(svg, {
    headers: { 'Content-Type': 'image/svg+xml', 'Cache-Control': 'public, max-age=86400', 'X-Content-Type-Options': 'nosniff' },
  });
}
