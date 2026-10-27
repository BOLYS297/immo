export const LOCALES = ['fr', 'en'] as const;
export type Locale = (typeof LOCALES)[number];
export const DEFAULT_LOCALE: Locale = 'fr';
export const COOKIE = 'immo-locale';

export const isLocale = (v: unknown): v is Locale => typeof v === 'string' && (LOCALES as readonly string[]).includes(v);

/** Lit Accept-Language : français si préféré, anglais pour toute autre langue, français sans information. */
export function detectLocale(header: string | null): Locale {
  if (!header) return DEFAULT_LOCALE;
  const langs = header
    .split(',')
    .map((p) => { const [tag, q] = p.trim().split(';q='); return { base: tag.toLowerCase().split('-')[0], q: q ? parseFloat(q) : 1 }; })
    .filter((l) => l.base && l.base !== '*')
    .sort((a, b) => b.q - a.q);
  const hit = langs.find((l) => isLocale(l.base));
  if (hit) return hit.base as Locale;
  return langs.length > 0 ? 'en' : DEFAULT_LOCALE;
}
