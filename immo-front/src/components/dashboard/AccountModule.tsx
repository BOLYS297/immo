'use client';
import { useState } from 'react';
import { useI18n } from '@/components/I18nProvider';
import PasswordInput from '@/components/ui/PasswordInput';

const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark dark:border-white/20 dark:bg-white/10 dark:text-white';

export default function AccountModule() {
  const a = useI18n().t.pages.dashboard.account;
  const [msg, setMsg] = useState<{ ok: boolean; text: string } | null>(null);

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const d = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('/api/portal/password', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ current: d.current, next: d.next }) });
    if (res.ok) { form.reset(); setMsg({ ok: true, text: a.saved }); } else setMsg({ ok: false, text: res.status === 403 ? a.wrong : a.invalid });
  }
  return (
    <section aria-labelledby="acc-title" className="max-w-md rounded-2xl border border-immo-dark/15 bg-white/70 p-6 dark:border-white/15 dark:bg-white/5">
      <h2 id="acc-title" className="text-xl font-semibold text-immo-dark dark:text-white">{a.title}</h2>
      <form onSubmit={onSubmit} className="mt-5 space-y-4">
        <label className="block font-semibold">{a.current}<PasswordInput name="current" required autoComplete="current-password" className={field} /></label>
        <label className="block font-semibold">{a.next}<PasswordInput name="next" required minLength={10} autoComplete="new-password" className={field} /></label>
        {msg && <p role={msg.ok ? 'status' : 'alert'} className={`rounded-xl px-4 py-3 text-sm ${msg.ok ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>{msg.text}</p>}
        <button type="submit" className="rounded-full bg-immo-cyan px-6 py-3 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{a.save}</button>
      </form>
    </section>
  );
}
