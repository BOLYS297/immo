import Link from 'next/link';
import { ArrowLeft } from 'lucide-react';
import RichText from '@/components/RichText';
import { formatDate } from '@/lib/articles';
import { getDict, getLocale } from '@/lib/locale';
import type { Post } from '@/server/cms-api';

/** Article Tech Insights : page publique /tech-insights/[slug] et aperçu d'un brouillon (/apercu). */
export default function ArticleView({ post: c }: { post: Post }) {
  const t = getDict();
  // Langue réellement servie : un article non traduit s'affiche en entier dans sa langue d'origine.
  const locale = c.locale;
  const author = t.pages.insights.author;
  const jsonLd = {
    '@context': 'https://schema.org', '@type': 'Article', headline: c.title, description: c.excerpt, datePublished: c.date, ...(c.updatedAt && { dateModified: c.updatedAt }), inLanguage: locale,
    author: { '@type': 'Organization', name: author }, publisher: { '@type': 'Organization', name: 'IMMO Digital' },
  };
  return (
    <main id="contenu" className="px-6 py-16 lg:py-24">
      <article lang={locale} className="mx-auto max-w-2xl">
        <Link href="/tech-insights" className="inline-flex items-center gap-2 font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4"><ArrowLeft size={16} aria-hidden /> {t.pages.common.allArticles}</Link>
        <h1 className="mt-8 text-4xl font-semibold leading-[1.12] tracking-tighter text-immo-dark dark:text-white">{c.title}</h1>
        <p className="mt-4 text-immo-dark/70 dark:text-immo-satin/70">{author} · <time dateTime={c.date}>{formatDate(c.date, getLocale())}</time> · {c.readingMinutes} {t.pages.common.readMinutes}</p>
        <RichText body={c.body} heading="h2" className="mt-10 space-y-5 text-lg leading-8" />
        <aside className="mt-14 rounded-2xl border-l-4 border-immo-cyan bg-white/70 p-7 dark:bg-white/5">
          <p className="text-xl font-semibold text-immo-dark dark:text-white">{t.pages.insights.similar}</p>
          <p className="mt-2">{t.pages.insights.similarText}</p>
          <Link href="/contact" className="mt-5 inline-block rounded-full bg-immo-cyan px-6 py-3 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{t.pages.common.launch}</Link>
        </aside>
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, '\u003c') }} />
      </article>
    </main>
  );
}
