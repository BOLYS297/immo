'use client';
import { useState } from 'react';
import { CheckCircle2, Loader2 } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import FormNotice from '@/components/legal/FormNotice';

const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark placeholder:text-immo-dark/60 dark:border-white/20 dark:bg-white/10 dark:text-white dark:placeholder:text-white/70';

/** Message libre (bloc « Écrivez-nous ») : transmis par /api/message à Laravel → admin « Messages ». */
export default function MessageForm() {
  const m = useI18n().t.pages.contact.message;
  const [status, setStatus] = useState<'idle' | 'sending' | 'done' | 'error'>('idle');
  const [error, setError] = useState('');

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setStatus('sending');
    const data = Object.fromEntries(new FormData(e.currentTarget).entries());
    try {
      const res = await fetch('/api/message', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      const out = (await res.json()) as { error?: string };
      if (!res.ok) throw new Error(out.error ?? m.failed);
      setStatus('done');
    } catch (err) {
      setError(err instanceof Error ? err.message : m.failed);
      setStatus('error');
    }
  }

  if (status === 'done') {
    return (
      <div role="status" className="flex gap-3 rounded-2xl border border-immo-cyan bg-white/70 p-7 dark:bg-white/5">
        <CheckCircle2 className="shrink-0 text-immo-cyan" aria-hidden />
        <p><strong>{m.sent}</strong> {m.sentText}</p>
      </div>
    );
  }
  return (
    <form onSubmit={onSubmit} className="grid gap-5 sm:grid-cols-2" aria-busy={status === 'sending'}>
      <label className="font-semibold">{m.name}<input name="name" required maxLength={100} autoComplete="name" className={field} /></label>
      <label className="font-semibold">{m.email}<input name="email" type="email" required maxLength={255} autoComplete="email" className={field} /></label>
      <label className="font-semibold">{m.phone}<input name="phone" type="tel" maxLength={30} pattern="\+?[0-9 \(\)\.\-]{6,30}" autoComplete="tel" inputMode="tel" placeholder="+237 6XX XXX XXX" className={field} /></label>
      <label className="font-semibold">{m.subject}<input name="subject" required maxLength={200} className={field} /></label>
      <label className="font-semibold sm:col-span-2">{m.message}<textarea name="message" required minLength={10} maxLength={4000} rows={5} className={field} /></label>
      <input name="website" tabIndex={-1} autoComplete="off" aria-hidden className="hidden" />
      <div className="space-y-3 sm:col-span-2">
        <FormNotice />
        {status === 'error' && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
        <button type="submit" disabled={status === 'sending'} className="inline-flex items-center gap-2 rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-70">
          {status === 'sending' && <Loader2 size={18} className="animate-spin" aria-hidden />}{m.submit}
        </button>
      </div>
    </form>
  );
}
