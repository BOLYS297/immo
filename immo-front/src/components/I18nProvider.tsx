'use client';
import { createContext, useContext } from 'react';
import type { Locale } from '@/lib/i18n';
import type { Dict } from '@/lib/dictionaries/fr';

const Ctx = createContext<{ locale: Locale; t: Dict } | null>(null);

export function I18nProvider({ locale, dict, children }: { locale: Locale; dict: Dict; children: React.ReactNode }) {
  return <Ctx.Provider value={{ locale, t: dict }}>{children}</Ctx.Provider>;
}
export function useI18n() {
  const c = useContext(Ctx);
  if (!c) throw new Error('I18nProvider manquant');
  return c;
}
