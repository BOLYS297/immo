import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import ArticleView from '@/components/insights/ArticleView';
import { getDict, getLocale } from '@/lib/locale';
import { DEFAULT_OG_IMAGE } from '@/lib/seo';
import { getPost } from '@/server/cms-api';

type Props = { params: { slug: string } };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const c = await getPost(params.slug, getLocale()).catch(() => null);
  if (!c) return { title: `${getDict().notFound.title} — IMMO Digital`, robots: { index: false } };
  const description = c.metaDescription ?? c.excerpt;
  return { title: c.metaTitle ?? `${c.title} — IMMO Digital`, description, alternates: { canonical: `/tech-insights/${c.slug}` }, openGraph: { title: c.title, description, type: 'article', url: `/tech-insights/${c.slug}`, publishedTime: c.date, images: [DEFAULT_OG_IMAGE] } };
}

export default async function ArticlePage({ params }: Props) {
  const c = await getPost(params.slug, getLocale());
  if (!c) notFound();
  return <ArticleView post={c} />;
}
