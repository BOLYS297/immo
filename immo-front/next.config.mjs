/** @type {import('next').NextConfig} */
const securityHeaders = [
  { key: 'X-Frame-Options', value: 'DENY' },
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'Strict-Transport-Security', value: 'max-age=63072000; includeSubDomains; preload' },
];

/**
 * Images de la médiathèque Laravel, optimisées par next/image : hôte de CMS_API_URL (disque « public »),
 * plus MEDIA_HOSTS (liste séparée par des virgules) pour un stockage objet (S3, R2, CDN). Lu au démarrage.
 */
const mediaPatterns = [process.env.CMS_API_URL, ...(process.env.MEDIA_HOSTS ?? '').split(',')]
  .map((v) => v?.trim())
  .filter(Boolean)
  .map((v) => new URL(v.includes('://') ? v : `https://${v}`))
  .map((u) => ({ protocol: u.protocol.replace(':', ''), hostname: u.hostname, ...(u.port && { port: u.port }), pathname: '/**' }));

/**
 * Aperçu de l'admin (/apercu) : seule page encadrable, et uniquement par l'administration Laravel
 * (origine de CMS_API_URL), pour l'aperçu en direct. Le reste du site refuse tout encadrement (DENY).
 */
// Origines autorisées : celle de CMS_API_URL, sa jumelle locale (localhost <-> 127.0.0.1, sinon l'aperçu reste vide
// quand l'admin est ouvert par l'autre nom), et ADMIN_ORIGINS (liste séparée par des virgules) si l'admin a une autre adresse.
const adminOrigins = [process.env.CMS_API_URL, ...(process.env.ADMIN_ORIGINS ?? '').split(',')]
  .map((v) => v?.trim())
  .filter(Boolean)
  .flatMap((v) => {
    const u = new URL(v);
    const twin = { localhost: '127.0.0.1', '127.0.0.1': 'localhost' }[u.hostname];
    return twin ? [u.origin, `${u.protocol}//${twin}${u.port ? `:${u.port}` : ''}`] : [u.origin];
  });
const previewHeaders = [
  { key: 'Content-Security-Policy', value: `frame-ancestors 'self' ${[...new Set(adminOrigins)].join(' ')}`.trim() },
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'Referrer-Policy', value: 'no-referrer' },
  { key: 'X-Robots-Tag', value: 'noindex, nofollow' },
  { key: 'Cache-Control', value: 'private, no-store' },
];

const nextConfig = {
  reactStrictMode: true,
  images: { remotePatterns: mediaPatterns },
  // Les navigateurs et robots demandent /favicon.ico d'office : on sert l'icône du site plutôt qu'une 404.
  async rewrites() {
    return [{ source: '/favicon.ico', destination: '/icons/icon-192.png' }];
  },
  async headers() {
    return [
      { source: '/((?!apercu).*)', headers: securityHeaders },
      { source: '/apercu', headers: previewHeaders },
      // Le service worker doit toujours être revalidé pour que les mises à jour arrivent immédiatement.
      { source: '/sw.js', headers: [{ key: 'Cache-Control', value: 'no-cache, no-store, must-revalidate' }, { key: 'Content-Type', value: 'application/javascript; charset=utf-8' }] },
    ];
  },
};
export default nextConfig;
