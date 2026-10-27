'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { PenLine } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import type { DocItem } from '@/lib/portal-types';

export default function BillingModule({ docs, readOnly }: { docs: DocItem[]; readOnly: boolean }) {
  const { t, locale } = useI18n();
  const d = t.pages.dashboard, b = d.billing;
  const tag = locale === 'fr' ? 'fr-FR' : 'en-GB';
  const xaf = new Intl.NumberFormat(tag, { style: 'currency', currency: 'XAF', maximumFractionDigits: 0 });
  const router = useRouter();
  const [error, setError] = useState('');
  const due = docs.filter((x) => x.kind === 'invoice' && x.status === 'pending').reduce((s, x) => s + (x.amount ?? 0), 0);

  async function sign(id: string) {
    setError('');
    const res = await fetch('/api/portal/sign', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
    if (res.ok) router.refresh(); else setError(d.signFailed);
  }
  // Cellules communes à la liste (téléphone) et au tableau (écrans plus larges).
  const kind = (x: DocItem) => b.kinds[x.kind === 'quote' ? 'quote' : x.kind === 'contract' ? 'contract' : 'invoice'];
  const amount = (x: DocItem) => (x.amount === null ? '—' : xaf.format(x.amount));
  const status = (x: DocItem) => (
    <>
      <span className="rounded-full bg-immo-sky/60 px-3 py-1 text-sm font-semibold text-immo-dark dark:bg-white/10 dark:text-immo-satin">{b.status[x.status]}</span>
      {x.signedAt && <span className="mt-1 block text-xs text-immo-dark/70 dark:text-immo-satin/70">{d.signedOn.replace('{date}', new Intl.DateTimeFormat(tag, { dateStyle: 'medium' }).format(new Date(x.signedAt)))}</span>}
    </>
  );
  const signButton = (x: DocItem) => x.status === 'toSign' && !readOnly && (
    <button type="button" onClick={() => sign(x.id)} className="inline-flex items-center gap-2 rounded-full bg-immo-cyan px-4 py-2 font-semibold text-immo-dark transition-colors hover:bg-immo-sky"><PenLine size={16} aria-hidden /> {b.sign}</button>
  );
  return (
    <section aria-labelledby="bill-title" className="rounded-2xl border border-immo-dark/15 bg-white/70 p-6 dark:border-white/15 dark:bg-white/5">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <h2 id="bill-title" className="text-xl font-semibold text-immo-dark dark:text-white">{b.title}</h2>
        <p className="font-semibold">{b.due} {xaf.format(due)}</p>
      </div>
      {docs.length === 0 ? <p className="mt-4">{d.empty}</p> : (
        <>
          {/* Téléphone : une fiche par document, statut et « Signer » visibles sans défilement horizontal. */}
          <ul className="mt-5 divide-y divide-immo-dark/10 sm:hidden dark:divide-white/10">
            {docs.map((x) => (
              <li key={x.id} className="space-y-2 py-4">
                <p className="font-medium">{x.title}</p>
                <p className="text-sm text-immo-dark/80 dark:text-immo-satin/80">{kind(x)} · {amount(x)}</p>
                <div>{status(x)}</div>
                {signButton(x)}
              </li>
            ))}
          </ul>
          {/* `relative` : contient le libellé sr-only (position absolue), qui sinon élargissait toute la page. */}
          <div className="relative mt-5 hidden overflow-x-auto sm:block">
            <table className="w-full min-w-[34rem] text-left">
              <thead><tr className="border-b border-immo-dark/15 dark:border-white/15">{b.cols.map((c) => <th key={c} className="py-3 pr-4">{c}</th>)}<th className="py-3"><span className="sr-only">{b.action}</span></th></tr></thead>
              <tbody>
                {docs.map((x) => (
                  <tr key={x.id} className="border-b border-immo-dark/10 dark:border-white/10">
                    <td className="py-3.5 pr-4 font-medium">{x.title}</td>
                    <td className="py-3.5 pr-4">{kind(x)}</td>
                    <td className="py-3.5 pr-4">{amount(x)}</td>
                    <td className="py-3.5 pr-4">{status(x)}</td>
                    <td className="py-3.5">{signButton(x)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      )}
      {error && <p role="alert" className="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
    </section>
  );
}
