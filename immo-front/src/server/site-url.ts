import { SITE_URL } from '@/lib/seo';

/**
 * Adresse absolue d'une page du site, pour NextResponse.redirect.
 * En production, le serveur Next autonome tourne derrière nginx et `req.url` porte son adresse d'écoute
 * (http://0.0.0.0:3000), injoignable pour le navigateur : on repart de l'hôte public transmis par le proxy
 * (X-Forwarded-Host / Host, fixés par nginx), sinon de NEXT_PUBLIC_SITE_URL.
 */
export function siteUrl(path: string, req: Request): URL {
  const first = (v: string | null) => v?.split(',')[0]?.trim() || null;
  const host = first(req.headers.get('x-forwarded-host')) ?? first(req.headers.get('host'));
  if (!host || host.startsWith('0.0.0.0')) return new URL(path, SITE_URL);
  const proto = first(req.headers.get('x-forwarded-proto')) ?? new URL(req.url).protocol.replace(':', '');
  return new URL(path, `${proto}://${host}`);
}
