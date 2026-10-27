import type { Metadata } from 'next';
import Blocks, { type BlockSearchParams } from '@/components/blocks/Blocks';
import { getDict, getLocale } from '@/lib/locale';
import { pageMetadata } from '@/lib/seo';
import { systemPage, type SystemSlug } from '@/lib/system-pages';
import { getPage } from './cms-api';

/** Métadonnées d'une page système : SEO saisi dans l'admin, sinon textes du dictionnaire. */
export async function systemMetadata(slug: SystemSlug): Promise<Metadata> {
  const page = await getPage(slug, getLocale());
  const fallback = systemPage(slug, getDict()).meta;
  return pageMetadata({ path: `/${slug}`, title: page?.meta.title ?? fallback.title, description: page?.meta.description ?? fallback.description, image: page?.meta.image, noindex: page?.meta.noindex });
}

/** Page système : sections du CMS, ou composition des dictionnaires si Laravel est injoignable. */
export async function SystemPage({ slug, searchParams = {} }: { slug: SystemSlug; searchParams?: BlockSearchParams }) {
  const page = await getPage(slug, getLocale());
  return (
    <main id="contenu">
      <Blocks sections={page?.sections ?? systemPage(slug, getDict()).sections} searchParams={searchParams} />
    </main>
  );
}
