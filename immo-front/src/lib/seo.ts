import type { Metadata } from 'next';
import type { MediaImage } from '@/lib/blocks';

/** Adresse publique du site (URL canoniques, plan du site, données structurées). */
export const SITE_URL = (process.env.NEXT_PUBLIC_SITE_URL ?? 'http://localhost:3000').replace(/\/+$/, '');

/**
 * Image de partage par défaut (fichier statique, couleurs et police du site). À fournir explicitement : une page
 * qui définit son propre Open Graph n'hérite pas de celui du layout.
 */
export const DEFAULT_OG_IMAGE = { url: '/images/og-default.png', width: 1200, height: 630, alt: 'IMMO Digital' };

/**
 * Métadonnées SEO d'une page : URL canonique, partage (Open Graph, X), indexation.
 */
export function pageMetadata({ path, title, description, image, noindex }: {
  path: string; title?: string | null; description?: string | null; image?: MediaImage | null; noindex?: boolean;
}): Metadata {
  const images = [image ? { url: image.url, width: image.width, height: image.height, alt: image.alt } : DEFAULT_OG_IMAGE];
  return {
    ...(title && { title }),
    ...(description && { description }),
    alternates: { canonical: path },
    openGraph: { type: 'website', url: path, images, ...(title && { title }), ...(description && { description }) },
    twitter: { card: 'summary_large_image', images, ...(title && { title }), ...(description && { description }) },
    ...(noindex && { robots: { index: false, follow: true } }),
  };
}

/** Texte brut d'un texte riche du CMS (**gras**, [lien](url), ## titre, - liste) : données structurées. */
export function plainText(markdown: string): string {
  return markdown
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/\*\*([^*]+)\*\*/g, '$1')
    .replace(/^#{1,6}\s+/gm, '')
    .replace(/^\s*-\s+/gm, '')
    .replace(/\n{2,}/g, '\n')
    .trim();
}

/** Balise <script> JSON-LD (les « < » sont échappés pour ne jamais fermer la balise). */
export const jsonLd = (data: object) => ({ __html: JSON.stringify(data).replace(/</g, '\\u003c') });
