'use client';
import { useRouter } from 'next/navigation';
import { useI18n } from '@/components/I18nProvider';

export default function LogoutButton({ to = '/connexion' }: { to?: string }) {
  const p = useI18n().t.pages.portalLogin;
  const router = useRouter();
  async function out() { await fetch('/api/auth/logout', { method: 'POST' }); router.push(to); router.refresh(); }
  return <button type="button" onClick={out} className="rounded-full border border-immo-dark/30 px-5 py-2.5 font-semibold transition-colors hover:bg-immo-sky/50 dark:border-white/30 dark:hover:bg-white/10">{p.signOut}</button>;
}
