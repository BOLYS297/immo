'use client';
import { Moon, Sun } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import { toggleTheme, useIsDark } from '@/lib/use-theme';

export default function ThemeToggle() {
  const { t } = useI18n();
  const dark = useIsDark();
  return (
    <button type="button" onClick={toggleTheme} aria-label={dark ? t.a11y.themeLight : t.a11y.themeDark}
      className="grid h-10 w-10 place-items-center rounded-full border border-immo-dark/20 text-immo-dark transition-colors hover:bg-immo-sky/60 dark:border-white/25 dark:text-white dark:hover:bg-white/10">
      {dark ? <Sun size={18} strokeWidth={2} /> : <Moon size={18} strokeWidth={2} />}
    </button>
  );
}
