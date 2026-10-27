'use client';
import { useI18n } from '@/components/I18nProvider';

export default function ErrorPage({ reset }: { error: Error; reset: () => void }) {
  const { t } = useI18n();
  return (
    <main id="contenu" className="px-6 py-32">
      <div className="mx-auto max-w-3xl">
        <h1 className="text-5xl font-semibold leading-[1.04] tracking-tighter text-immo-dark sm:text-6xl dark:text-white">{t.error.title}</h1>
        <p className="mt-6 text-lg text-immo-dark/85 dark:text-immo-satin/90">{t.error.text}</p>
        <button type="button" onClick={reset} className="mt-10 rounded-full bg-immo-cyan px-7 py-3.5 font-semibold text-immo-dark transition-colors hover:bg-immo-sky">{t.error.retry}</button>
      </div>
    </main>
  );
}
