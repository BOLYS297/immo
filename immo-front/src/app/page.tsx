import type { Metadata } from 'next';
import Blocks from '@/components/blocks/Blocks';
import type { Section } from '@/lib/blocks';
import { getLocale } from '@/lib/locale';
import { pageMetadata } from '@/lib/seo';
import { getPage } from '@/server/cms-api';

// Composition de repli si le CMS est injoignable ou la page « home » absente : textes des dictionnaires.
const FALLBACK: Section[] = (['hero', 'statement', 'services', 'pillars', 'diagnostic', 'terminal', 'portal_preview', 'cta_band'] as const)
  .map((type) => ({ id: type, type, content: {} }));

export async function generateMetadata(): Promise<Metadata> {
  const page = await getPage('home', getLocale());
  return pageMetadata({ path: '/', title: page?.meta.title, description: page?.meta.description, image: page?.meta.image, noindex: page?.meta.noindex });
}

export default async function HomePage({ searchParams }: { searchParams: { domaine?: string } }) {
  const page = await getPage('home', getLocale());
  return (
    <main id="contenu">
      <Blocks sections={page?.sections ?? FALLBACK} searchParams={{ domaine: searchParams.domaine }} />
    </main>
  );
}
