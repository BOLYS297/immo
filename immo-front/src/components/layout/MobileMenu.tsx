'use client';
import { useState } from 'react';
import { Menu, X } from 'lucide-react';
import { useI18n } from '@/components/I18nProvider';
import type { MenuLink } from '@/server/cms-api';
import LanguageSwitcher from './LanguageSwitcher';
import SiteLink from './SiteLink';

/** Liens de l'en-tête (admin « Menus ») sauf ceux réservés à l'ordinateur ; le bouton d'action est repris sous 640 px. */
export default function MobileMenu({ links, cta }: { links: MenuLink[]; cta: { label: string; url: string } | null }) {
  const { t } = useI18n();
  const [open, setOpen] = useState(false);
  return (
    <div className="lg:hidden">
      <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} aria-controls="mobile-nav" aria-label={open ? t.a11y.closeMenu : t.a11y.openMenu}
        className="grid h-10 w-10 place-items-center rounded-full border border-immo-dark/20 dark:border-white/25">
        {open ? <X size={18} /> : <Menu size={18} />}
      </button>
      {open && (
        <nav id="mobile-nav" aria-label={t.a11y.mobileNav} className="absolute inset-x-0 top-full border-b border-immo-dark/10 bg-immo-satin px-6 py-6 shadow-xl dark:border-white/10 dark:bg-immo-dark">
          <ul className="space-y-1">
            {links.map((l) => (
              <li key={`${l.url}-${l.label}`}><SiteLink url={l.url} newTab={l.newTab} onClick={() => setOpen(false)} className="block rounded-xl px-3 py-3 text-lg font-semibold hover:bg-immo-sky/50 dark:hover:bg-white/10">{l.label}</SiteLink></li>
            ))}
          </ul>
          {/* Le bouton de l'en-tête est masqué sous 640 px : il est repris ici. */}
          {cta && <SiteLink url={cta.url} onClick={() => setOpen(false)} className="mx-3 mt-4 block rounded-full bg-immo-cyan px-5 py-3 text-center font-semibold text-immo-dark transition-colors hover:bg-immo-sky sm:hidden">{cta.label}</SiteLink>}
          <div className="mt-4 px-3 sm:hidden"><LanguageSwitcher /></div>
        </nav>
      )}
    </div>
  );
}
