import Link from 'next/link';
import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

export default function CtaBand({ content }: { content?: BlockContent<'cta_band'> }) {
  const c = { ...getDict().cta, primary_href: '/contact', secondary_href: '/services', ...content };
  return (
    <section aria-labelledby="cta-title" className="px-6 py-28">
      <div className="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-immo-dark p-10 text-immo-satin dark:border dark:border-white/15 dark:bg-black/30 lg:p-20">
        <div aria-hidden className="pointer-events-none absolute -bottom-24 -right-10 h-[28rem] w-[28rem] bg-contain bg-no-repeat opacity-[0.12]" style={{ backgroundImage: 'url(/images/symbol-mark.png)' }} />
        <h2 id="cta-title" className="relative max-w-2xl text-4xl font-semibold leading-[1.05] tracking-tighter text-white sm:text-6xl">{c.title}</h2>
        <p className="relative mt-6 max-w-xl text-lg text-immo-satin/90">{c.text}</p>
        <div className="relative mt-10 flex flex-wrap gap-4">
          <Link href={c.primary_href} className="rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{c.primary}</Link>
          <Link href={c.secondary_href} className="rounded-full border border-white/40 px-7 py-3.5 font-semibold text-white transition-colors hover:bg-white/10">{c.secondary}</Link>
        </div>
      </div>
    </section>
  );
}
