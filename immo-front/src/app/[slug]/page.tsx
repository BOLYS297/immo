import type { Metadata } from 'next';
import { notFound, permanentRedirect } from 'next/navigation';
import Blocks from '@/components/blocks/Blocks';
import { getDict, getLocale } from '@/lib/locale';
import { pageMetadata } from '@/lib/seo';
import { getPage } from '@/server/cms-api';

/**
 * Pages créées dans l'admin Laravel (Pages & Sections), servies à /<slug>.
 * Les routes fixes du site (/services, /contact…) restent prioritaires : un slug identique n'est jamais affiché ici.
 */
type Props = { params: { slug: string }; searchParams: { domaine?: string } };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const page = await getPage(params.slug, getLocale());
  if (!page) return { title: `${getDict().notFound.title} — IMMO Digital`, robots: { index: false } };
  return pageMetadata({ path: `/${params.slug}`, title: page.meta.title ?? `${page.title} — IMMO Digital`, description: page.meta.description, image: page.meta.image, noindex: page.meta.noindex });
}

export default async function CmsPage({ params, searchParams }: Props) {
  if (params.slug === 'home') permanentRedirect('/');
  const page = await getPage(params.slug, getLocale());
  if (!page) notFound();
  return (
    <main id="contenu">
      <Blocks sections={page.sections} searchParams={{ domaine: searchParams.domaine }} />
    </main>
  );
}
