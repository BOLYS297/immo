'use client';
import { ExternalLink, FileText } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import type { Deliverable } from '@/lib/portal-types';

export default function DeliverablesModule({ items }: { items: Deliverable[] }) {
  const { t } = useI18n();
  const d = t.pages.dashboard;
  return (
    <section aria-labelledby="deliv-title" className="rounded-2xl border border-immo-dark/15 bg-white/70 p-6 dark:border-white/15 dark:bg-white/5">
      <h2 id="deliv-title" className="text-xl font-semibold text-immo-dark dark:text-white">{d.tabs[1]}</h2>
      {items.length === 0 ? <p className="mt-4">{d.empty}</p> : (
        <ul className="mt-3 divide-y divide-immo-dark/10 dark:divide-white/10">
          {items.map((i) => (
            <li key={i.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
              <div className="flex items-start gap-3"><FileText size={20} className="mt-0.5 shrink-0 text-immo-cyan" aria-hidden /><div><p className="font-semibold">{i.title}</p>{i.note && <p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">{i.note}</p>}</div></div>
              {i.url && <a href={i.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-full border border-current px-4 py-2 font-semibold">{d.openLink} <ExternalLink size={16} aria-hidden /></a>}
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
