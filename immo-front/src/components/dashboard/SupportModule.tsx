'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useI18n } from '@/components/I18nProvider';
import type { Ticket } from '@/lib/portal-types';

const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark dark:border-white/20 dark:bg-white/10 dark:text-white';

export default function SupportModule({ tickets, readOnly }: { tickets: Ticket[]; readOnly: boolean }) {
  const { t } = useI18n();
  const d = t.pages.dashboard, s = d.support;
  const router = useRouter();
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    setBusy(true); setError('');
    const res = await fetch('/api/portal/tickets', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ title: data.subject, message: data.message, priority: Number(data.priority) }) });
    setBusy(false);
    if (res.ok) { form.reset(); router.refresh(); } else setError(d.ticketFailed);
  }
  return (
    <div className="grid gap-8 lg:grid-cols-[1fr_1.2fr]">
      {!readOnly && (
        <section aria-labelledby="new-ticket" className="h-fit rounded-2xl border border-immo-dark/15 bg-white/70 p-6 dark:border-white/15 dark:bg-white/5">
          <h2 id="new-ticket" className="text-xl font-semibold text-immo-dark dark:text-white">{s.newTitle}</h2>
          <p className="mt-2 text-sm text-immo-dark/70 dark:text-immo-satin/70">{s.newNote}</p>
          <form onSubmit={onSubmit} className="mt-5 space-y-4" aria-busy={busy}>
            <label className="block font-semibold">{s.subject}<input name="subject" required maxLength={120} className={field} /></label>
            <label className="block font-semibold">{d.message}<textarea name="message" required rows={4} maxLength={2000} className={field} /></label>
            <label className="block font-semibold">{s.priority}<select name="priority" defaultValue="0" className={field}>{s.priorities.map((p, i) => <option key={p} value={i}>{p}</option>)}</select></label>
            {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
            <button type="submit" disabled={busy} className="rounded-full bg-immo-cyan px-6 py-3 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-70">{s.send}</button>
          </form>
        </section>
      )}
      <section aria-labelledby="tickets-title" className={`rounded-2xl border border-immo-dark/15 bg-white/70 p-6 dark:border-white/15 dark:bg-white/5 ${readOnly ? 'lg:col-span-2' : ''}`}>
        <h2 id="tickets-title" className="text-xl font-semibold text-immo-dark dark:text-white">{s.listTitle}</h2>
        {tickets.length === 0 ? <p className="mt-4">{d.empty}</p> : (
          <ul className="mt-3 divide-y divide-immo-dark/10 dark:divide-white/10">
            {tickets.map((x) => (
              <li key={x.id} className="py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div><p className="font-semibold">{x.title}</p><p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">{s.prioLabel.replace('{p}', s.priorities[x.priority].toLowerCase())}</p></div>
                  <span className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{s.statuses[x.status === 'open' ? 'open' : x.status === 'progress' ? 'progress' : 'resolved']}</span>
                </div>
                {x.message && <p className="mt-2 whitespace-pre-wrap">{x.message}</p>}
                {x.reply && <p className="mt-3 rounded-xl border-l-4 border-immo-cyan bg-immo-satin p-3 whitespace-pre-wrap dark:bg-white/10"><span className="block text-sm font-semibold">{d.reply}</span>{x.reply}</p>}
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
