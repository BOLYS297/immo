'use client';
import Link from 'next/link';
import { useI18n } from '@/components/I18nProvider';

/** Information obligatoire au moment de la collecte (loi n° 2024/017). */
export default function FormNotice() {
  const l = useI18n().t.legal;
  return (
    <p className="text-sm text-immo-dark/70 dark:text-immo-satin/70">
      {l.formNote}{' '}
      <Link href="/confidentialite" className="font-semibold underline decoration-immo-cyan decoration-2 underline-offset-4">{l.privacyLink}</Link>.
    </p>
  );
}
