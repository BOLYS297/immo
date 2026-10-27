'use client';
import { useState } from 'react';
import { CheckCircle2, Loader2 } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import { DOMAIN_KEYS, type DomainKey } from '@/lib/services-data';
import FormNotice from '@/components/legal/FormNotice';

const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark placeholder:text-immo-dark/60 dark:border-white/20 dark:bg-white/10 dark:text-white dark:placeholder:text-white/70';

export default function QuoteForm({ defaultDomain }: { defaultDomain?: DomainKey }) {
  const f = useI18n().t.pages.services.form;
  const [status, setStatus] = useState<'idle' | 'sending' | 'done' | 'error'>('idle');
  const [message, setMessage] = useState('');

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setStatus('sending');
    const data = Object.fromEntries(new FormData(e.currentTarget).entries());
    try {
      const res = await fetch('/api/quote', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      const out = (await res.json()) as { error?: string };
      if (!res.ok) throw new Error(out.error ?? f.failed);
      setStatus('done');
    } catch (err) {
      setMessage(err instanceof Error ? err.message : f.failed);
      setStatus('error');
    }
  }

  if (status === 'done') {
    return (
      <div role="status" className="flex gap-3 rounded-2xl border border-immo-cyan bg-white/70 p-7 dark:bg-white/5">
        <CheckCircle2 className="shrink-0 text-immo-cyan" aria-hidden />
        <p><strong>{f.sent}</strong> {f.sentText}</p>
      </div>
    );
  }
  return (
    <form onSubmit={onSubmit} className="grid gap-5 sm:grid-cols-2" aria-busy={status === 'sending'}>
      <label className="font-semibold">{f.name}<input name="name" required maxLength={80} autoComplete="name" className={field} /></label>
      <label className="font-semibold">{f.email}<input name="email" type="email" required maxLength={120} autoComplete="email" className={field} /></label>
      <label className="font-semibold">{f.phone}<input name="phone" type="tel" required minLength={6} maxLength={30} pattern="\+?[0-9 \(\)\.\-]{6,30}" autoComplete="tel" inputMode="tel" placeholder="+237 6XX XXX XXX" className={field} /></label>
      <label className="font-semibold">{f.company}<input name="company" required maxLength={100} autoComplete="organization" className={field} /></label>
      <label className="font-semibold sm:col-span-2">{f.domain}
        <select name="domain" defaultValue={defaultDomain ?? 'ia'} className={field}>{DOMAIN_KEYS.map((k, i) => <option key={k} value={k}>{f.domainShort[i]}</option>)}</select>
      </label>
      <label className="font-semibold sm:col-span-2">{f.details}<textarea name="details" required minLength={20} maxLength={1500} rows={5} className={field} placeholder={f.placeholder} /></label>
      <input name="website" tabIndex={-1} autoComplete="off" aria-hidden className="hidden" />
      <div className="sm:col-span-2 space-y-3">
        <FormNotice />
        {status === 'error' && <p role="alert" className="mb-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{message}</p>}
        <button type="submit" disabled={status === 'sending'} className="inline-flex items-center gap-2 rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-70">
          {status === 'sending' && <Loader2 size={18} className="animate-spin" aria-hidden />}{f.submit}
        </button>
      </div>
    </form>
  );
}
