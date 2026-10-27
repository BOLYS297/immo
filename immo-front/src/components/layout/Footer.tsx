import type { CSSProperties } from 'react';
import { Facebook, Instagram, Linkedin, Twitter, Youtube, type LucideIcon } from 'lucide-react';
import Logo from '@/components/ui/Logo';
import type { SocialKey } from '@/lib/site';
import { getDict, getLocale } from '@/lib/locale';
import { loadSite } from '@/server/site';
import ContactList from './ContactList';
import SiteLink from './SiteLink';

const SOCIAL: Record<SocialKey, { icon: LucideIcon; name: string }> = {
  linkedin: { icon: Linkedin, name: 'LinkedIn' }, facebook: { icon: Facebook, name: 'Facebook' }, instagram: { icon: Instagram, name: 'Instagram' },
  x: { icon: Twitter, name: 'X' }, youtube: { icon: Youtube, name: 'YouTube' },
};

export default async function Footer() {
  const t = getDict();
  const site = await loadSite(getLocale());
  return (
    <footer className="relative overflow-hidden border-t border-white/15 bg-immo-dark text-immo-satin">
      <div aria-hidden className="pointer-events-none absolute -bottom-24 -right-16 h-[26rem] w-[26rem] bg-contain bg-no-repeat opacity-[0.07]" style={{ backgroundImage: 'url(/images/symbol-mark.png)' }} />
      {/* Grande largeur : logo, une piste par colonne de liens (nombre réglé dans l'admin), coordonnées. */}
      <div className="relative mx-auto grid max-w-7xl gap-12 px-6 py-16 md:grid-cols-3 lg:[grid-template-columns:var(--footer-cols)]"
        style={{ '--footer-cols': `1.4fr ${'1fr '.repeat(site.footer.length)}1.3fr` } as CSSProperties}>
        <div className="md:col-span-3 lg:col-span-1">
          <span className="text-white"><Logo tone="dark" /></span>
          {site.slogan && <p className="mt-5 max-w-xs text-immo-sky">{site.slogan}</p>}
          {site.social.length > 0 && (
            <ul className="mt-6 flex gap-3">
              {site.social.map(({ key, url }) => {
                const { icon: Icon, name } = SOCIAL[key];
                return (
                  <li key={key}>
                    <a href={url} target="_blank" rel="noopener noreferrer" aria-label={name} className="grid h-10 w-10 place-items-center rounded-full border border-white/25 transition-colors hover:border-immo-cyan hover:text-immo-cyan">
                      <Icon size={18} aria-hidden />
                    </a>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
        {site.footer.map((c) => (
          <nav key={c.label} aria-label={c.label}>
            <h2 className="font-semibold text-white">{c.label}</h2>
            <ul className="mt-4 space-y-2.5">
              {c.links.map((l) => <li key={`${l.url}-${l.label}`}><SiteLink url={l.url} newTab={l.newTab} className="transition-colors hover:text-immo-cyan">{l.label}</SiteLink></li>)}
            </ul>
          </nav>
        ))}
        <address className="not-italic">
          <h2 className="font-semibold text-white">{t.footer.contact}</h2>
          <ContactList contact={site.contact} whatsappLabel={t.footer.whatsapp} linkClass="hover:text-immo-cyan" className="mt-4 space-y-3" />
        </address>
      </div>
      <div className="relative mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 border-t border-white/15 px-6 py-6 text-sm text-immo-sky">
        <p>© {new Date().getFullYear()} {site.name}. {t.footer.rights}</p>
        {site.legal.length > 0 && (
          <nav aria-label={t.footer.legal} className="flex flex-wrap gap-x-6 gap-y-2">
            {site.legal.map((l) => <SiteLink key={`${l.url}-${l.label}`} url={l.url} newTab={l.newTab} className="transition-colors hover:text-immo-cyan">{l.label}</SiteLink>)}
          </nav>
        )}
      </div>
    </footer>
  );
}
