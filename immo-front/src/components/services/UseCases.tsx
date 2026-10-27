import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

/** Missions types (bloc « use_cases ») ; champ vide = texte du dictionnaire. */
export default function UseCases({ content: c = {} }: { content?: BlockContent<'use_cases'> }) {
  const s = getDict().pages.services;
  const cases = c.cases?.length ? c.cases : s.cases;
  return (
    <section aria-labelledby="cas-title" className="px-6 py-16">
      <div className="mx-auto max-w-7xl">
        <h2 id="cas-title" className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{c.title || s.casesTitle}</h2>
        <div className="mt-10 grid gap-8 lg:grid-cols-3">
          {cases.map((x, i) => (
            <article key={i} className="border-t-2 border-immo-dark pt-6 dark:border-immo-cyan">
              <h3 className="text-xl font-semibold text-immo-dark dark:text-white">{x.title}</h3>
              <dl className="mt-4 space-y-3">
                <div><dt className="font-semibold">{c.situation_label || s.situation}</dt><dd className="text-immo-dark/85 dark:text-immo-satin/90">{x.situation}</dd></div>
                <div><dt className="font-semibold">{c.solution_label || s.solution}</dt><dd className="text-immo-dark/85 dark:text-immo-satin/90">{x.solution}</dd></div>
                <div><dt className="font-semibold">{c.result_label || s.result}</dt><dd className="text-lg font-semibold text-immo-dark dark:text-immo-sky">{x.result}</dd></div>
              </dl>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
