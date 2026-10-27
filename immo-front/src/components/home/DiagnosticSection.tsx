import type { BlockContent } from '@/lib/blocks';
import { getDict } from '@/lib/locale';
import DiagnosticWidget from './DiagnosticWidget';

export default function DiagnosticSection({ content }: { content?: BlockContent<'diagnostic'> }) {
  const d = { ...getDict().diagnostic, ...content };
  return (
    <section aria-labelledby="diag-title" className="px-6 py-28">
      <div className="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2 lg:items-center">
        <div>
          <h2 id="diag-title" className="max-w-lg text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{d.title}</h2>
          <p className="mt-5 max-w-md text-lg text-immo-dark/85 dark:text-immo-satin/90">{d.text}</p>
        </div>
        <DiagnosticWidget />
      </div>
    </section>
  );
}
