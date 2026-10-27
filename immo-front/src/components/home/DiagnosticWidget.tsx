'use client';
import { useState } from 'react';
import { AnimatePresence, motion } from 'framer-motion';
import { Loader2, ShieldCheck, Smartphone, Sparkles, Workflow, type LucideIcon } from 'lucide-react';
import Link from 'next/link';
import { useI18n } from '@/components/I18nProvider';
import type { DiagnosticAxis, DiagnosticReport } from '@/lib/diagnostic';

const icons: Record<DiagnosticAxis['id'], LucideIcon> = { automatisation: Workflow, application: Smartphone, infrastructure: ShieldCheck };

type State = { status: 'idle' } | { status: 'loading' } | { status: 'error'; message: string } | { status: 'done'; report: DiagnosticReport };

export default function DiagnosticWidget() {
  const { t } = useI18n();
  const d = t.diagnostic;
  const [query, setQuery] = useState('');
  const [state, setState] = useState<State>({ status: 'idle' });

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setState({ status: 'loading' });
    try {
      const res = await fetch('/api/diagnostic', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ query }) });
      const data = (await res.json()) as DiagnosticReport & { error?: string };
      if (!res.ok) throw new Error(data.error ?? d.unavailable);
      setState({ status: 'done', report: data });
    } catch (err) {
      setState({ status: 'error', message: err instanceof Error ? err.message : d.errorGeneric });
    }
  }
  const loading = state.status === 'loading';

  return (
    <div className="w-full max-w-xl">
      <form onSubmit={onSubmit} className="flex flex-col gap-3 sm:flex-row" aria-busy={loading}>
        <label htmlFor="diag-query" className="sr-only">{d.label}</label>
        <input id="diag-query" type="text" value={query} onChange={(e) => setQuery(e.target.value)} maxLength={120} required placeholder={d.placeholder}
          className="min-w-0 flex-1 rounded-full border border-immo-dark/25 bg-white px-5 py-3.5 text-immo-dark placeholder:text-immo-dark/60 dark:border-white/20 dark:bg-white/10 dark:text-white dark:placeholder:text-white/70" />
        <button type="submit" disabled={loading} className="inline-flex items-center justify-center gap-2 rounded-full bg-immo-cyan px-6 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-70">
          {loading ? <Loader2 size={18} className="animate-spin" /> : <Sparkles size={18} />}
          {loading ? d.loading : d.button}
        </button>
      </form>
      <div aria-live="polite" className="mt-4">
        {state.status === 'error' && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{state.message}</p>}
        <AnimatePresence>
          {state.status === 'done' && (
            <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} className="overflow-hidden rounded-2xl border border-immo-dark/15 bg-white/70 dark:border-white/15 dark:bg-white/5">
              <p className="px-5 pt-4 font-semibold">{d.reportFor.replace('{name}', state.report.subject)}</p>
              <ul className="divide-y divide-immo-dark/10 dark:divide-white/10">
                {state.report.axes.map((axis, i) => {
                  const Icon = icons[axis.id];
                  return (
                    <motion.li key={axis.id} initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.15 * (i + 1) }} className="flex gap-3 px-5 py-4">
                      <Icon size={22} className="mt-0.5 shrink-0 text-immo-cyan" aria-hidden />
                      <div><p className="font-semibold">{axis.title}</p><p className="text-sm text-immo-dark/80 dark:text-immo-satin/85">{axis.detail}</p></div>
                    </motion.li>
                  );
                })}
              </ul>
              <div className="px-5 pb-4"><Link href="/contact" className="font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4">{d.cta}</Link></div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </div>
  );
}
