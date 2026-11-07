import type { Dict } from '@/lib/dictionaries/fr';
import type { Locale } from '@/lib/i18n';
import { DOMAIN_KEYS } from '@/lib/services-data';
import { getServices } from '@/server/cms-api';

/**
 * Pôle proposé au visiteur (chiffrage, prise de rendez-vous) : chaque service publié dans l'admin est un pôle
 * (immo-back App\Services\Poles). `label` : choix du rendez-vous ; `short` : liste du chiffrage ;
 * `hint` : texte d'accompagnement ; `pole` : nom affiché dans la confirmation.
 */
export type Pole = { key: string; label: string; short: string; hint: string; pole: string };

/** Services publiés ; sinon (aucun service, Laravel injoignable) les trois pôles historiques du dictionnaire. */
export async function getPoles(locale: Locale, t: Dict): Promise<Pole[]> {
  const services = await getServices(locale).catch(() => []);
  const c = t.pages.contact;
  if (services.length > 0) {
    return services.map((s) => {
      const label = s.title ?? s.slug;
      return { key: s.slug, label, short: label, hint: s.description ?? '', pole: c.poleOf.replace('{name}', label) };
    });
  }
  return DOMAIN_KEYS.map((key, i) => ({ key, label: c.needs[i].label, short: t.pages.services.form.domainShort[i], hint: c.needs[i].pole, pole: c.needs[i].pole }));
}
