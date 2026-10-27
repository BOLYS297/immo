'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Loader2 } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import PasswordInput from '@/components/ui/PasswordInput';

const field = 'mt-1.5 w-full rounded-xl border border-immo-dark/25 bg-white px-4 py-3 text-immo-dark dark:border-white/20 dark:bg-white/10 dark:text-white';

/** Connexion des clients au portail (comptes créés dans l'admin Laravel). */
export default function LoginForm() {
  const p = useI18n().t.pages.portalLogin;
  const router = useRouter();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setBusy(true); setError('');
    const data = Object.fromEntries(new FormData(e.currentTarget).entries());
    try {
      const res = await fetch('/api/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      const out = (await res.json()) as { error?: string };
      if (!res.ok) throw new Error(out.error ?? p.failed);
      router.push('/dashboard'); router.refresh();
    } catch (err) { setError(err instanceof Error ? err.message : p.failed); setBusy(false); }
  }
  return (
    <form onSubmit={onSubmit} className="mt-8 space-y-5" aria-busy={busy}>
      <label className="block font-semibold">{p.email}<input name="email" type="email" required autoComplete="username" className={field} /></label>
      <label className="block font-semibold">{p.password}<PasswordInput name="password" required autoComplete="current-password" className={field} /></label>
      {error && <p role="alert" className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">{error}</p>}
      <button type="submit" disabled={busy} className="inline-flex items-center gap-2 rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky disabled:opacity-70">
        {busy && <Loader2 size={18} className="animate-spin" aria-hidden />}{p.signIn}
      </button>
    </form>
  );
}
