import type { Metadata } from 'next';
import Link from 'next/link';
import { getDict } from '@/lib/locale';

export function generateMetadata(): Metadata {
  return { title: `${getDict().notFound.title} — IMMO Digital`, robots: { index: false } };
}

export default function NotFound() {
  const t = getDict();
  return (
    <main id="contenu" className="px-6 py-32">
      <div className="mx-auto max-w-3xl">
        <p className="font-semibold text-immo-dark/70 dark:text-immo-sky">404</p>
        <h1 className="mt-4 text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl dark:text-white">{t.notFound.title}</h1>
        <p className="mt-6 text-lg text-immo-dark/85 dark:text-immo-satin/90">{t.notFound.text}</p>
        <Link href="/" className="mt-10 inline-block rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{t.notFound.back}</Link>
      </div>
    </main>
  );
}
