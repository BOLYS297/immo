import type { MetadataRoute } from 'next';

export default function manifest(): MetadataRoute.Manifest {
  return {
    id: '/',
    name: 'IMMO Digital',
    short_name: 'IMMO',
    description: 'Libérez le potentiel de vos idées. IA, Web & Mobile, Infrastructure, et votre Portail Client.',
    lang: 'fr',
    start_url: '/?source=pwa',
    scope: '/',
    display: 'standalone',
    background_color: '#273480',
    theme_color: '#273480',
    categories: ['business', 'productivity'],
    icons: [
      { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
      { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
      { src: '/icons/icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
    ],
    shortcuts: [
      { name: 'Espace client', short_name: 'Portail', url: '/dashboard', icons: [{ src: '/icons/icon-192.png', sizes: '192x192' }] },
      { name: 'Lancer un projet', short_name: 'Contact', url: '/contact', icons: [{ src: '/icons/icon-192.png', sizes: '192x192' }] },
    ],
  };
}
