import type { Dict } from '@/lib/dictionaries/fr';
import type { MenuLink, SiteData } from '@/server/cms-api';

/**
 * Réglages et menus du site, gérés dans l'admin Laravel (« Réglages du site », « Menus »).
 * Les valeurs ci-dessous ne servent que si Laravel est injoignable : ce sont les mêmes que les valeurs
 * par défaut de immo-back/config/cms.php (settings) et de la migration des menus.
 */
export type Contact = { email: string | null; phones: { label: string; href: string }[]; whatsappUrl: string | null; address: string[] };
export type SocialKey = 'linkedin' | 'facebook' | 'instagram' | 'x' | 'youtube';
export type SiteView = {
  name: string;
  slogan: string | null;
  contact: Contact;
  social: { key: SocialKey; url: string }[];
  cta: { label: string; url: string } | null;
  seo: { title: string; description: string };
  /** « always » : animation du logo jouée même si le visiteur demande à réduire les animations (réglage de l'admin). */
  logoAnimation: 'auto' | 'always';
  header: MenuLink[];
  footer: { label: string; links: MenuLink[] }[];
  legal: MenuLink[];
};

const SOCIAL: SocialKey[] = ['linkedin', 'facebook', 'instagram', 'x', 'youtube'];
const lines = (v: string | null) => (v ?? '').split('\n').map((l) => l.trim()).filter(Boolean);
const digits = (v: string) => v.replace(/[^\d+]/g, '');

export function contactFrom(s: SiteData['settings']): Contact {
  const wa = s.contact_whatsapp ? digits(s.contact_whatsapp).replace(/^\+/, '') : '';
  return {
    email: s.contact_email,
    phones: lines(s.contact_phones).map((label) => ({ label, href: digits(label) })),
    whatsappUrl: wa ? `https://wa.me/${wa}` : null,
    address: lines(s.contact_address),
  };
}

export function siteView(data: SiteData, t: Dict): SiteView {
  const s = data.settings;
  return {
    name: s.site_name ?? 'IMMO Digital',
    slogan: s.site_slogan,
    contact: contactFrom(s),
    social: SOCIAL.flatMap((key) => (s[`social_${key}`] ? [{ key, url: s[`social_${key}`] as string }] : [])),
    cta: s.header_cta_label && s.header_cta_url ? { label: s.header_cta_label, url: s.header_cta_url } : null,
    seo: { title: s.seo_title ?? t.meta.title, description: s.seo_description ?? t.meta.description },
    logoAnimation: s.logo_animation === 'always' ? 'always' : 'auto',
    header: data.menus.header,
    footer: data.menus.footer.filter((c) => c.links.length > 0),
    legal: data.menus.legal,
  };
}

/** Site tel qu'avant le CMS, depuis les dictionnaires : utilisé quand Laravel ne répond pas. */
export function fallbackSite(t: Dict): SiteData {
  const link = (url: string, label: string, display: MenuLink['display'] = 'all'): MenuLink => ({ label, url, newTab: false, display });
  return {
    settings: {
      site_name: 'IMMO Digital', site_slogan: t.footer.slogan, contact_email: null,
      contact_phones: '+237 678 261 382\n+237 694 072 875', contact_whatsapp: '+237 678 261 382', contact_address: t.footer.address.join('\n'),
      social_linkedin: null, social_facebook: null, social_instagram: null, social_x: null, social_youtube: null,
      header_cta_label: t.nav.cta, header_cta_url: '/contact', seo_title: t.meta.title, seo_description: t.meta.description, logo_animation: 'auto',
    },
    menus: {
      header: [
        link('/services', t.nav.solutions), link('/a-propos', t.nav.about), link('/recommandations', t.nav.reco), link('/tech-insights', t.nav.resources),
        link('/contact', t.nav.contact, 'mobile'), link('/dashboard', t.footer.client, 'mobile'),
      ],
      footer: [
        { label: t.footer.solutions, links: (['ia', 'web', 'infra'] as const).map((k) => link(`/services?domaine=${k}#chiffrage`, t.domains[k])) },
        { label: t.footer.company, links: [link('/a-propos', t.nav.about), link('/recommandations', t.nav.reco), link('/contact', t.nav.contact)] },
        { label: t.footer.resources, links: [link('/tech-insights', t.footer.insights), link('/dashboard', t.footer.client)] },
      ],
      legal: [link('/mentions-legales', t.footer.legalNotice), link('/confidentialite', t.footer.privacy)],
    },
  };
}
