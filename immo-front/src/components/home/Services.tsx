import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

// Liens des lignes par défaut (dictionnaire) ; une ligne saisie dans le CMS porte son propre lien.
const HREFS = ['/services?domaine=ia#chiffrage', '/services?domaine=web#chiffrage', '/services?domaine=web#chiffrage', '/services?domaine=infra#chiffrage'];

export default function Services({ content }: { content?: BlockContent<'services'> }) {
  const s = { ...getDict().services, ...content };
  return (
    <section id="poles" aria-labelledby="services-title" className="relative z-0 px-6 py-28">
      <div className="mx-auto max-w-7xl">
        <div className="flex flex-wrap items-end justify-between gap-6">
          <h2 id="services-title" className="max-w-2xl text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{s.title}</h2>
          <Link href="/services" className="font-semibold text-immo-dark underline decoration-immo-cyan decoration-2 underline-offset-4 dark:text-white">{s.all}</Link>
        </div>
        <ul className="mt-14 border-b border-immo-dark/20 dark:border-white/20">
          {s.rows.map((r: { title?: string; text?: string; href?: string }, i) => (
            <li key={i} className="border-t border-immo-dark/20 dark:border-white/20">
              <Link href={r.href ?? HREFS[i] ?? '/services'} className="group grid items-center gap-3 py-9 md:grid-cols-[4rem_1.2fr_1fr_2rem] md:gap-8">
                <span className="font-semibold text-immo-dark/60 dark:text-immo-sky">0{i + 1}</span>
                <h3 className="text-2xl font-semibold tracking-tight text-immo-dark transition-transform duration-300 group-hover:translate-x-2 sm:text-3xl dark:text-white">{r.title}</h3>
                <p className="text-immo-dark/85 dark:text-immo-satin/90">{r.text}</p>
                <ArrowUpRight size={26} aria-hidden className="hidden text-immo-cyan transition-transform duration-300 group-hover:-translate-y-1 group-hover:translate-x-1 md:block" />
              </Link>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}
