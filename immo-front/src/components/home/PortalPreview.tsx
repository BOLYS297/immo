'use client';
import { useState } from 'react';
import Link from 'next/link';
import { FolderGit2, KanbanSquare, LifeBuoy, Receipt, type LucideIcon } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import type { BlockContent } from '@/lib/blocks';

type TabId = 'kanban' | 'deliverables' | 'invoices' | 'support';
const TABS: { id: TabId; icon: LucideIcon }[] = [
  { id: 'kanban', icon: KanbanSquare }, { id: 'deliverables', icon: FolderGit2 }, { id: 'invoices', icon: Receipt }, { id: 'support', icon: LifeBuoy },
];
const COL_BORDER = ['border-immo-dark/30 dark:border-white/30', 'border-immo-cyan', 'border-green-500'];

export default function PortalPreview({ content }: { content?: BlockContent<'portal_preview'> }) {
  const { t } = useI18n();
  const p = { ...t.portal, ...content };
  const [tab, setTab] = useState<TabId>('kanban');
  const idx = TABS.findIndex((x) => x.id === tab);

  return (
    <section aria-labelledby="portail-title" className="px-6 py-28">
      <div className="mx-auto grid max-w-7xl items-center gap-12 lg:grid-cols-[0.8fr_1.2fr]">
        <div>
          <h2 id="portail-title" className="text-4xl font-semibold tracking-tighter text-immo-dark sm:text-5xl dark:text-white">{p.title}</h2>
          <p className="mt-4 max-w-md text-immo-dark/85 dark:text-immo-satin/90">{p.text}</p>
          <Link href="/dashboard" className="mt-8 inline-block rounded-full bg-immo-cyan px-6 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{p.button}</Link>
        </div>
        <div className="overflow-hidden rounded-2xl border border-immo-dark/20 bg-white shadow-xl dark:border-white/15 dark:bg-immo-dark dark:shadow-none">
          <div role="tablist" aria-label={p.tablist} className="flex gap-1 overflow-x-auto border-b border-immo-dark/10 bg-immo-satin px-3 pt-3 dark:border-white/15 dark:bg-black/25">
            {TABS.map(({ id, icon: Icon }, i) => (
              <button key={id} role="tab" id={`tab-${id}`} aria-selected={tab === id} aria-controls="portal-panel" onClick={() => setTab(id)}
                className={`inline-flex shrink-0 items-center gap-2 rounded-t-lg px-4 py-2.5 text-sm font-semibold transition-colors ${tab === id ? 'bg-white text-immo-dark dark:bg-immo-dark dark:text-white' : 'text-immo-dark/70 hover:text-immo-dark dark:text-immo-satin/70 dark:hover:text-white'}`}>
                <Icon size={16} aria-hidden /> {p.tabs[i]}
              </button>
            ))}
          </div>
          <div id="portal-panel" role="tabpanel" aria-labelledby={`tab-${tab}`} className="min-h-[18rem] p-5 text-immo-dark dark:text-immo-satin">
            {tab === 'kanban' ? (
              <div className="grid gap-4 sm:grid-cols-3">
                {p.cols.map((col, ci) => (
                  <div key={col.name} className="rounded-xl bg-immo-satin p-3 dark:bg-white/5">
                    <p className="mb-3 text-sm font-bold">{col.name}</p>
                    <ul className="space-y-2">{col.cards.map((c) => <li key={c} className={`rounded-lg border-l-4 bg-white px-3 py-2.5 text-sm dark:bg-white/10 ${COL_BORDER[ci]}`}>{c}</li>)}</ul>
                  </div>
                ))}
              </div>
            ) : (
              <ul className="divide-y divide-immo-dark/10 dark:divide-white/10">
                {p.rows[tab].map(([label, status]) => (
                  <li key={label} className="flex items-center justify-between gap-4 py-3.5">
                    <span className="font-medium">{label}</span>
                    <span className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{status}</span>
                  </li>
                ))}
              </ul>
            )}
            <p className="mt-4 text-sm text-immo-dark/70 dark:text-immo-satin/70" data-tab-index={idx}>{p.fictive}</p>
          </div>
        </div>
      </div>
    </section>
  );
}
