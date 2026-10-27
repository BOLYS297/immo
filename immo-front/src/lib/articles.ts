import type { Locale } from '@/lib/i18n';

// Le corps des articles (saisi dans l'admin Laravel) est rendu par src/components/RichText.tsx.

export const formatDate = (iso: string, locale: Locale) =>
  new Intl.DateTimeFormat(locale === 'fr' ? 'fr-FR' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(iso));
