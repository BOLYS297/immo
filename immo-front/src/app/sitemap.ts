import type { MetadataRoute } from 'next';
import { SITE_URL } from '@/lib/seo';
import { SYSTEM_SLUGS } from '@/lib/system-pages';
import { getPageSlugs, getPosts } from '@/server/cms-api';

export const dynamic = 'force-dynamic';

// Pages servies par le code (hors CMS).
const CODE_PAGES = ['/mentions-legales', '/confidentialite'];

/**
 * Plan du site : pages publiées du CMS (accueil compris, sans celles marquées « ne pas indexer »),
 * date de leur dernière publication, articles, pages légales. CMS injoignable : pages système et légales.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [pages, articles] = await Promise.all([getPageSlugs().catch(() => null), getPosts('fr').catch(() => [])]);
  const cms = pages
    ? pages.filter((p) => !p.noindex).map((p) => ({ url: `${SITE_URL}${p.slug === 'home' ? '' : `/${p.slug}`}`, ...(p.updated_at && { lastModified: p.updated_at }) }))
    : ['', ...SYSTEM_SLUGS.map((s) => `/${s}`)].map((path) => ({ url: `${SITE_URL}${path}` }));
  return [
    ...cms,
    ...CODE_PAGES.map((path) => ({ url: `${SITE_URL}${path}` })),
    ...articles.map((a) => ({ url: `${SITE_URL}/tech-insights/${a.slug}`, lastModified: a.updatedAt ?? a.date })),
  ];
}
