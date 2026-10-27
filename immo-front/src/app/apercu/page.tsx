import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { Eye } from 'lucide-react';
import Blocks from '@/components/blocks/Blocks';
import ArticleView from '@/components/insights/ArticleView';
import type { CmsPage } from '@/lib/blocks';
import { getLocale } from '@/lib/locale';
import { cmsForm, type Post } from '@/server/cms-api';

/**
 * Aperçu demandé depuis l'admin Laravel (brouillon de page, ancienne version, article non publié).
 * Le jeton (créé par Laravel, valable 2 h) est échangé contre le contenu via l'API protégée par la clé du front.
 * Jamais mis en cache ni indexé ; encadrable uniquement par l'admin (next.config.mjs).
 */
export const dynamic = 'force-dynamic';

export const metadata: Metadata = { title: 'Aperçu — IMMO Digital', robots: { index: false, follow: false } };

type Preview =
  | { kind: 'page'; version: 'draft' | 'revision'; revisionDate: string | null; page: CmsPage }
  | { kind: 'post'; published: boolean; post: Post };

const LABELS = {
  fr: { draft: 'Aperçu du brouillon : ces modifications ne sont pas encore en ligne.', revision: 'Aperçu d’une ancienne version, publiée le', post: 'Aperçu de l’article', unpublished: '(non publié)' },
  en: { draft: 'Draft preview: these changes are not live yet.', revision: 'Preview of a previous version, published on', post: 'Article preview', unpublished: '(not published)' },
};

export default async function PreviewPage({ searchParams }: { searchParams: { jeton?: string } }) {
  const token = searchParams.jeton ?? '';
  if (!/^[A-Za-z0-9]{48}$/.test(token)) notFound();
  const locale = getLocale();
  const { status, data } = await cmsForm<{ data: Preview }>('GET', `/preview/${token}?locale=${locale}`);
  if (status !== 200 || !data?.data) notFound();
  const preview = data.data;
  const l = LABELS[locale];

  const banner = preview.kind === 'post'
    ? `${l.post} ${preview.published ? '' : l.unpublished}`
    : preview.version === 'draft'
      ? l.draft
      : `${l.revision} ${new Date(preview.revisionDate ?? '').toLocaleString(locale === 'fr' ? 'fr-FR' : 'en-GB', { dateStyle: 'long', timeStyle: 'short' })}`;

  return (
    <>
      <p role="status" className="flex items-center justify-center gap-2 bg-immo-cyan px-6 py-2 text-center text-sm font-semibold text-immo-dark">
        <Eye size={16} aria-hidden /> {banner}
      </p>
      {preview.kind === 'post'
        ? <ArticleView post={preview.post} />
        : <main id="contenu"><Blocks sections={preview.page.sections} anchors /></main>}
    </>
  );
}
