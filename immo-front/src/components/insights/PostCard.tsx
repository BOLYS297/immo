import Link from 'next/link';
import { formatDate } from '@/lib/articles';
import type { Locale } from '@/lib/i18n';
import type { PostSummary } from '@/server/cms-api';

/** Carte d'article Tech Insights (page /tech-insights et bloc « Derniers articles »). */
export default function PostCard({ post: c, locale, readMinutes, heading: H = 'h2' }: { post: PostSummary; locale: Locale; readMinutes: string; heading?: 'h2' | 'h3' }) {
  return (
    <li className="flex flex-col rounded-2xl border border-immo-dark/15 bg-white/70 p-7 transition-colors hover:border-immo-cyan dark:border-white/15 dark:bg-white/5">
      <ul className="flex flex-wrap gap-2">{c.tags.map((tag) => <li key={tag} className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-medium text-immo-dark dark:bg-white/10 dark:text-immo-satin">{tag}</li>)}</ul>
      <H className="mt-4 text-xl font-semibold text-immo-dark dark:text-white"><Link href={`/tech-insights/${c.slug}`} className="hover:text-immo-cyan">{c.title}</Link></H>
      <p className="mt-3 flex-1 text-immo-dark/85 dark:text-immo-satin/90">{c.excerpt}</p>
      <p className="mt-5 text-sm text-immo-dark/70 dark:text-immo-satin/70"><time dateTime={c.date}>{formatDate(c.date, locale)}</time> · {c.readingMinutes} {readMinutes}</p>
    </li>
  );
}
