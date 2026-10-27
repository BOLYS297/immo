import { cache } from 'react';
import type { Locale } from '@/lib/i18n';
import { dictionaries } from '@/lib/dictionaries';
import { fallbackSite, siteView, type SiteView } from '@/lib/site';
import { getSite } from './cms-api';

/** Réglages et menus du site pour la requête en cours (un seul appel partagé par l'en-tête, le pied de page et la page). */
export const loadSite = cache(async (locale: Locale): Promise<SiteView> => {
  const t = dictionaries[locale];
  return siteView((await getSite(locale)) ?? fallbackSite(t), t);
});
