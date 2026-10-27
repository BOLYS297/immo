import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

export default function Pillars({ content }: { content?: BlockContent<'pillars'> }) {
  const pillars = { ...getDict().pillars, ...content };
  return (
    <section aria-labelledby="piliers-title" className="mx-3 rounded-[2.5rem] bg-immo-sky px-6 py-28 dark:bg-black/25 sm:mx-6">
      <div className="mx-auto max-w-7xl">
        <h2 id="piliers-title" className="max-w-2xl text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{pillars.title}</h2>
        <ul className="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
          {pillars.items.map((p, i) => (
            <li key={i} className="border-t-2 border-immo-dark pt-6 dark:border-immo-cyan">
              <p className="text-sm font-semibold text-immo-dark/80 dark:text-immo-sky">0{i + 1}</p>
              <h3 className="mt-3 text-2xl font-semibold tracking-tight text-immo-dark dark:text-white">{p.title}</h3>
              <p className="mt-3 text-immo-dark/85 dark:text-immo-satin/90">{p.text}</p>
            </li>
          ))}
        </ul>
      </div>
    </section>
  );
}
