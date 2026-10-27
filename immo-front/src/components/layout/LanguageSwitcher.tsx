'use client';
import { useRouter } from 'next/navigation';
import { useI18n } from '@/components/I18nProvider';
import { COOKIE, LOCALES, type Locale } from '@/lib/i18n';

export default function LanguageSwitcher() {
  const { locale, t } = useI18n();
  const router = useRouter();
  function choose(l: Locale) {
    document.cookie = `${COOKIE}=${l}; path=/; max-age=31536000; samesite=lax`;
    router.refresh();
  }
  return (
    <div role="group" aria-label={t.a11y.language} className="flex rounded-full border border-immo-dark/20 p-0.5 dark:border-white/25">
      {LOCALES.map((l) => (
        <button key={l} type="button" lang={l} aria-pressed={locale === l} onClick={() => choose(l)}
          className={`rounded-full px-3 py-1.5 text-sm font-semibold transition-colors ${locale === l ? 'bg-immo-dark text-white dark:bg-immo-cyan dark:text-immo-dark' : 'hover:bg-immo-sky/50 dark:hover:bg-white/10'}`}>
          {l.toUpperCase()}
        </button>
      ))}
    </div>
  );
}
