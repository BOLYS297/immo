import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';

/** Méthode en étapes (bloc « methodology ») ; champ vide = texte du dictionnaire. */
export default function Methodology({ content: c = {} }: { content?: BlockContent<'methodology'> }) {
  const t = getDict();
  const s = t.pages.services;
  const steps = c.steps?.length ? c.steps : s.steps;
  return (
    <section aria-labelledby="methode-title" className="px-6 py-16">
      <div className="mx-auto max-w-7xl">
        <h2 id="methode-title" className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{c.title || s.methodTitle}</h2>
        <ol className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {steps.map((st, i) => (
            <li key={i} className="border-t-4 border-immo-cyan pt-5">
              <p className="font-semibold text-immo-dark/70 dark:text-immo-sky">{c.step_label || t.pages.common.step} {i + 1}</p>
              <h3 className="mt-1 text-xl font-semibold text-immo-dark dark:text-white">{st.title}</h3>
              <p className="mt-2 text-immo-dark/85 dark:text-immo-satin/90">{st.text}</p>
            </li>
          ))}
        </ol>
      </div>
    </section>
  );
}
